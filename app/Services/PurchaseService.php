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

            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'account_id'  => $data['account_id'],
                'order_date'  => $data['order_date'],
                'status'      => 'ordered',
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'payment_status' => 'credit',
            ]);

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
            $account = Account::lockForUpdate()->findOrFail($order->account_id);
            $isCreditPurchase = $account->type === 'credit_payable';

            if (!$isCreditPurchase && $account->balance < $totalAmount) {
                throw new \Exception("Insufficient funds in the selected account to pay for these goods.");
            }

            // A: Update Inventory
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

            // B: Financials & Status
            if ($isCreditPurchase) {
                Transaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'in',
                    'amount'           => $totalAmount,
                    'description'      => "Purchase Order #{$order->id} Received (Debt Added)",
                    'reference_type'   => PurchaseOrder::class,
                    'reference_id'     => $order->id,
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $totalAmount);
                $order->update(['status' => 'received', 'payment_status' => 'credit', 'amount_paid' => 0]);
            } else {
                Transaction::create([
                    'account_id'       => $account->id,
                    'type'             => 'out',
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

                $order->update(['status' => 'received', 'payment_status' => 'paid', 'amount_paid' => $totalAmount]);
            }
        });
    }

    // UPDATED: Now accepts $paymentAmount for partial payments!
    public function settleCreditOrder(PurchaseOrder $order, int $cashAccountId, float $paymentAmount): void
    {
        DB::transaction(function () use ($order, $cashAccountId, $paymentAmount) {
            $payableAccount = $order->account;
            if ($payableAccount->type != 'credit_payable') {
                throw new \Exception("This order is not a credit purchase.");
            }

            // Prevent overpaying
            $balanceDue = $order->total_amount - $order->amount_paid;
            if ($paymentAmount > $balanceDue) {
                throw new \Exception("Payment exceeds the remaining balance of LKR " . number_format($balanceDue, 2));
            }

            $cashAccount = Account::lockForUpdate()->findOrFail($cashAccountId);

            if ($cashAccount->balance < $paymentAmount) {
                throw new \Exception("Insufficient funds in the cash account.");
            }

            // 1. Pull cash OUT
            $cashAccount->decrement('balance', $paymentAmount);
            if ($cashAccount->capital_pool >= $paymentAmount) {
                $cashAccount->decrement('capital_pool', $paymentAmount);
            } else {
                $remaining = $paymentAmount - $cashAccount->capital_pool;
                $cashAccount->update(['capital_pool' => 0]);
                $cashAccount->decrement('profit_pool', $remaining);
            }

            Transaction::create([
                'account_id'       => $cashAccount->id,
                'type'             => 'out',
                'amount'           => $paymentAmount,
                'description'      => "Payment sent for Purchase Order #{$order->id}",
                'reference_type'   => PurchaseOrder::class,
                'reference_id'     => $order->id,
                'transaction_date' => now(),
            ]);

            // 2. Reduce the Debt
            $payableAccount->decrement('balance', $paymentAmount);

            Transaction::create([
                'account_id'       => $payableAccount->id,
                'type'             => 'out',
                'amount'           => $paymentAmount,
                'description'      => "Debt Partial/Full Clear for Purchase Order #{$order->id}",
                'reference_type'   => PurchaseOrder::class,
                'reference_id'     => $order->id,
                'transaction_date' => now(),
            ]);

            // 3. Update the Order's Paid Status
            $newAmountPaid = $order->amount_paid + $paymentAmount;
            $order->update([
                'amount_paid' => $newAmountPaid,
                'payment_status' => $newAmountPaid >= $order->total_amount ? 'paid' : 'partial',
            ]);
        });
    }
}
