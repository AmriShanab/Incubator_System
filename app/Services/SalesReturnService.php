<?php

namespace App\Services;

use App\Models\Account;
use App\Models\SalesReturn;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    /**
     * Process a return, restock inventory, and safely deduct funds.
     * Throws an Exception if funds are insufficient.
     */

    public function processRefund(SalesReturn $returnRecord, int $accountId): void
    {
        DB::transaction(function () use ($returnRecord, $accountId) {
            $invoice = $returnRecord->invoice;
            $refundAmount = (float) $returnRecord->refund_amount;

            $account = Account::lockForUpdate()->find($accountId);

            if (!$account) {
                throw new \Exception("Account not found.");
            }

            if ($account->balance < $refundAmount) {
                throw new \Exception("Insufficient funds in account to process refund.");
            }

            $totalCapitalCost = 0;

            foreach ($invoice->items as $item) {
                $product = $item->sellable;
                if ($product) {
                    $product->increment('current_stock', (float) $item->quantity);
                }

                $totalCapitalCost += ((float) ($item->unit_cost ?? 0)) * (float) $item->quantity;
            }

            if($refundAmount > 0) {
                $profitToDeduct = max(0, $refundAmount - $totalCapitalCost);

                Transaction::create([
                    'account_id' => $accountId,
                    'type' => 'out',
                    'amount' => $refundAmount, 
                    'description' => "Refund for Sales Return #{$returnRecord->id}",
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $returnRecord->id,
                    'transaction_date' => now(),
                ]);

                $account->decrement('balance', $refundAmount);
                $account->decrement('capital_pool', $totalCapitalCost);
                $account->decrement('profit_pool', $profitToDeduct);
            }

            $returnRecord->update(['status' => 'completed']);
        });
    }
}
