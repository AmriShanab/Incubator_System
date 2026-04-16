<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function processSale(array $cart, float $amountPaid, int $customerId, int $accountId, float $discountAmount = 0): Invoice 
    {
        return DB::transaction(function () use ($cart, $amountPaid, $customerId, $accountId, $discountAmount) {
            $subTotal = collect($cart)->sum('row_total');
            $grandTotal = max(0, $subTotal - $discountAmount);

            // Determine how much is cash vs how much is debt
            $actualPaid = min($amountPaid, $grandTotal);
            $debtAmount = $grandTotal - $actualPaid;

            $account = Account::lockForUpdate()->find($accountId);

            // Determine the status automatically based on the math
            $paymentStatus = match (true) {
                $debtAmount == $grandTotal => 'credit',
                $debtAmount > 0 => 'partial',
                default => 'paid',
            };

            $invoice = Invoice::create([
                'customer_id'    => $customerId,
                'invoice_date'   => now(),
                'status'         => 'delivered',
                // If it's full credit, label it "Credit". Otherwise, show the cash account used.
                'payment_method' => $debtAmount == $grandTotal ? 'Credit' : $account->name,
                'account_id'     => $accountId,
                'total_amount'   => $grandTotal,
                'amount_paid'    => $actualPaid,
                'payment_status' => $paymentStatus,
            ]);

            $totalCost = 0;

            foreach ($cart as $item) {
                $invoice->items()->create([
                    'sellable_type' => $item['type'],
                    'sellable_id'   => $item['id'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'unit_cost'     => $item['unit_cost'],
                    'row_total'     => $item['row_total'],
                ]);

                $totalCost += (float) $item['unit_cost'] * (float) $item['quantity'];

                $product = $item['type']::find($item['id']);
                if ($product) {
                    if ($product->current_stock < $item['quantity']) {
                        Notification::make()->title("Insufficient stock for {$product->name}")->warning()->send();
                    }
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            $invoice->updateQuietly(['total_cost' => $totalCost]);

            // ──────────────────────────────────────────────────────────
            // 1. HANDLE CASH PORTION (Money received today)
            // ──────────────────────────────────────────────────────────
            if ($actualPaid > 0) {
                $profit = max(0, $actualPaid - $totalCost);

                $invoice->transactions()->create([
                    'account_id' => $account->id,
                    'type' => 'in', 
                    'amount' => $actualPaid,
                    'description' => "Sale (Cash Received): Invoice #{$invoice->id}",
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $actualPaid);
                $account->increment('capital_pool', min($actualPaid, $totalCost));
                $account->increment('profit_pool', $profit);
            }

            // ──────────────────────────────────────────────────────────
            // 2. HANDLE CREDIT PORTION (Money owed to us)
            // ──────────────────────────────────────────────────────────
            if ($debtAmount > 0) {
                // Find the main Accounts Receivable account
                $receivableAccount = Account::where('type', 'credit_receivable')->first();
                
                if (!$receivableAccount) {
                    throw new \Exception("No 'Accounts Receivable' account found. Please create an account in the system with the type 'credit_receivable'.");
                }

                $invoice->transactions()->create([
                    'account_id' => $receivableAccount->id,
                    'type' => 'in', // This increases the balance of debt owed to us
                    'amount' => $debtAmount,
                    'description' => "Sale (Unpaid Debt): Invoice #{$invoice->id}",
                    'transaction_date' => now(),
                ]);

                $receivableAccount->increment('balance', $debtAmount);
            }

            return $invoice;
        });
    }
}