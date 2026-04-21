<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceReversionController extends Controller
{
    public function revertInvoice(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            // Lock the invoice to prevent race conditions during reversal
            $invoice = Invoice::lockForUpdate()->findOrFail($id);

            if ($invoice->status === 'cancelled') {
                return redirect()->back()->with('error', 'This invoice is already cancelled.');
            }

            // ──────────────────────────────────────────────────────────
            // 1. RESTOCK INVENTORY
            // ──────────────────────────────────────────────────────────
            foreach ($invoice->items as $item) {
                // MorphTo relationships can be resolved dynamically
                $product = $item->sellable_type::find($item->sellable_id);

                if ($product) {
                    $product->increment('current_stock', $item->quantity);
                }
            }

            // ──────────────────────────────────────────────────────────
            // 2. REVERSE ACCOUNT BALANCES & POOLS
            // ──────────────────────────────────────────────────────────
            $account = $invoice->account;
            $amountToReverse = (float) $invoice->amount_paid;
            $totalCost = (float) $invoice->total_cost;

            if ($amountToReverse > 0 && $account) {
                // Calculate exactly how much went to capital vs profit originally
                $capitalReversed = min($amountToReverse, $totalCost);
                $profitReversed = max(0, $amountToReverse - $capitalReversed);

                // Pull the money OUT of the account
                $account->decrement('balance', $amountToReverse);

                // Safely decrement the pools (preventing negative pools if possible)
                if ($account->capital_pool >= $capitalReversed) {
                    $account->decrement('capital_pool', $capitalReversed);
                } else {
                    $account->update(['capital_pool' => 0]);
                }

                if ($account->profit_pool >= $profitReversed) {
                    $account->decrement('profit_pool', $profitReversed);
                } else {
                    $account->update(['profit_pool' => 0]);
                }

                // Log the reversal transaction for the audit trail
                $invoice->transactions()->create([
                    'account_id' => $account->id,
                    'type' => 'out', // 'out' removes the money we previously received
                    'amount' => $amountToReverse,
                    'description' => "SUPER ADMIN REVERSAL: Voided Invoice #{$invoice->id}",
                    'transaction_date' => now(),
                ]);
            }

            // ──────────────────────────────────────────────────────────
            // 3. VOID THE INVOICE
            // ──────────────────────────────────────────────────────────
            $invoice->update([
                'status' => 'cancelled',       // Matches your existing database ENUM
                'payment_status' => 'credit',  // Setting to credit because the paid amount is now 0
                'amount_paid' => 0,            // Reset the paid amount to 0
            ]);

            return redirect()->back()->with('success', "Invoice #{$invoice->id} successfully reverted. Stock and accounts restored.");
        });
    }
}
