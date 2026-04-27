<?php

namespace App\Services;

use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function createOrder(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $totalAmount = (float) $data['total_amount'];

            // 1. Create the Order Document (No money moves yet)
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'account_id'  => $data['account_id'],
                'order_date'  => $data['order_date'],
                'status'      => 'ordered',
                'total_amount'=> $totalAmount,
            ]);

            // 2. Attach the Items
            foreach ($items as $item) {
                $order->items()->create([
                    'purchasable_type' => $item['purchasable_type'],
                    'purchasable_id'   => $item['purchasable_id'],
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'row_total'        => $item['row_total'],
                ]);
            }

            return $order;
        });
    }

    public function receiveOrder(PurchaseOrder $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->status != 'ordered') {
                throw new \Exception("Only orders with 'ordered' status can be received.");
            }

            $totalAmount = $order->total_amount;
            
            // Lock the account to prevent race conditions during receipt
            $account = Account::lockForUpdate()->findOrFail($order->account_id);
            $isCreditPurchase = $account->type === 'credit_payable';

            // Check if we have enough cash (if paying immediately on delivery)
            if (!$isCreditPurchase && $account->balance < $totalAmount) {
                throw new \Exception("Insufficient funds in the selected account to pay for these goods.");
            }

            // ──────────────────────────────────────────────────────────
            // STEP A: UPDATE PHYSICAL INVENTORY & COSTS
            // ──────────────────────────────────────────────────────────
            foreach ($order->items as $item) {
                $purchasable = $item->purchasable;
                if ($purchasable) {
                    $purchasable->increment('current_stock', (float) $item->quantity);
                    
                    if (class_basename($purchasable) === 'Material') {
                        $purchasable->update(['cost_per_unit' => $item->unit_cost]);
                    } elseif (class_basename($purchasable) === 'Accessory') {
                        $purchasable->update(['cost_price' => $item->unit_cost]);
                    }
                }
            }

            // ──────────────────────────────────────────────────────────
            // STEP B: EXECUTE FINANCIAL TRANSACTIONS
            // ──────────────────────────────────────────────────────────
            if ($isCreditPurchase) {
                // We received the goods on credit -> Increase our Debt (Accounts Payable)
                Transaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'in', // 'in' means liability/debt increased
                    'amount'           => $totalAmount,
                    'description'      => "Purchase Order #{$order->id} Received (Debt Added)",
                    'reference_type'   => PurchaseOrder::class,
                    'reference_id'     => $order->id,
                    'transaction_date' => now(),
                ]);
                
                $account->increment('balance', $totalAmount);

            } else {
                // We paid upfront -> Decrease Cash Drawer & Pools
                Transaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'out', // 'out' means cash left the business
                    'amount'           => $totalAmount,
                    'description'      => "Purchase Order #{$order->id} Received (Paid)",
                    'reference_type'   => PurchaseOrder::class,
                    'reference_id'     => $order->id,
                    'transaction_date' => now(),
                ]);

                $account->decrement('balance', $totalAmount);

                if ($account->capital_pool >= $totalAmount) {
                    $account->decrement('capital_pool', $totalAmount);
                } else {
                    $remaining = $totalAmount - $account->capital_pool;
                    $account->update(['capital_pool' => 0]);
                    $account->decrement('profit_pool', $remaining);
                }
            }

            // ──────────────────────────────────────────────────────────
            // STEP C: MARK ORDER AS COMPLETED
            // ──────────────────────────────────────────────────────────
            $order->update(['status' => 'received']);
        });
    }

    public function settleCreditOrder(PurchaseOrder $order, int $cashAccountId): void
    {
        DB::transaction(function () use ($order, $cashAccountId) {
            $payableAccount = $order->account;
            if ($payableAccount->type != 'credit_payable') {
                throw new \Exception("This order is not a credit purchase.");
            }

            $cashAccount = Account::lockForUpdate()->findOrFail($cashAccountId);
            $amount = $order->total_amount;

            if ($cashAccount->balance < $amount) {
                throw new \Exception("Insufficient funds in the cash account.");
            }

            // 1. Pull the cash OUT of the drawer
            $cashAccount->decrement('balance', $amount);
            if ($cashAccount->capital_pool >= $amount) {
                $cashAccount->decrement('capital_pool', $amount);
            } else {
                $remaining = $amount - $cashAccount->capital_pool;
                $cashAccount->update(['capital_pool' => 0]);
                $cashAccount->decrement('profit_pool', $remaining);
            }

            Transaction::create([
                'account_id'       => $cashAccount->id,
                'type'             => 'out',
                'amount'           => $amount,
                'description'      => "Payment sent for Purchase Order #{$order->id}",
                'reference_type'   => PurchaseOrder::class,
                'reference_id'     => $order->id,
                'transaction_date' => now(),
            ]);

            // 2. Reduce the Debt (Accounts Payable)
            $payableAccount->decrement('balance', $amount);

            Transaction::create([
                'account_id'       => $payableAccount->id,
                'type'             => 'out', // 'out' clears a liability balance
                'amount'           => $amount,
                'description'      => "Debt Cleared for Purchase Order #{$order->id}",
                'reference_type'   => PurchaseOrder::class,
                'reference_id'     => $order->id,
                'transaction_date' => now(),
            ]);

            // Update the PO so it reflects the drawer it was finally paid from
            $order->update(['account_id' => $cashAccount->id]);
        });
    }
}