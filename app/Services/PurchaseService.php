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
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);
            $totalAmount = (float) $data['total_amount'];

            $isCreditPurchase = $account->type === 'credit_payable';

            if (!$isCreditPurchase && $account->balance < $totalAmount) {
                throw new \Exception("Insufficient funds in the account.");
            }

            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'account_id' => $data['account_id'],
                'order_date' => $data['order_date'],
                'status' => 'ordered',
                'total_amount' => $totalAmount,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'material_id' => $item['material_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'row_total'   => $item['row_total'],
                ]);
            }

            if ($isCreditPurchase) {
                Transaction::create([
                    'account_id' => $account->id,
                    'type' => 'in', // 'in' means we received an invoice/debt
                    'amount' => $totalAmount,
                    'description' => "Purchase Order #{$order->id} (Bought on Credit)",
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $order->id,
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $totalAmount);
            } else {
                Transaction::create([
                    'account_id' => $account->id,
                    'type' => 'out', // 'out' means we paid money
                    'amount' => $totalAmount,
                    'description' => "Purchase Order #{$order->id} (Paid)",
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $order->id,
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

            // FIX: Moved this outside the if/else block so it ALWAYS returns the order!
            return $order;
        });
    }

    public function receiveOrder(PurchaseOrder $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->status != 'ordered') {
                throw new \Exception("Only orders with 'ordered' status can be received.");
            }

            foreach ($order->items as $item) {
                $material = $item->material;
                if ($material) {
                    $material->increment('current_stock', (float) $item->quantity);
                    $material->update(['cost_per_unit' => $item->unit_cost]);
                }
            }

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

            $cashAccount->decrement('balance', $amount);
            if ($cashAccount->capital_pool >= $amount) {
                $cashAccount->decrement('capital_pool', $amount);
            } else {
                $remaining = $amount - $cashAccount->capital_pool;
                $cashAccount->update(['capital_pool' => 0]);
                $cashAccount->decrement('profit_pool', $remaining);
            }

            Transaction::create([
                'account_id' => $cashAccount->id,
                'type' => 'out',
                'amount' => $amount,
                'description' => "Payment for Purchase Order #{$order->id}",
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $order->id,
                'transaction_date' => now(),

            ]);

            $payableAccount->decrement('balance', $amount);

            Transaction::create([
                'account_id' => $payableAccount->id,
                'type' => 'out', // 'out' reduces a liability balance
                'amount' => $amount,
                'description' => "Debt Cleared for Purchase Order #{$order->id}",
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $order->id,
                'transaction_date' => now(),
            ]);

            $order->update(['account_id' => $cashAccount->id]);
        });
    }
}
