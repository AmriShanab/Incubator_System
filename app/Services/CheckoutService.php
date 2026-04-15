<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class CheckoutService
{
    public function processSale(array $cart, float $amountPaid, int $customerId, int $accountId, float $discountAmount = 0): Invoice 
    {
        return DB::transaction(function () use ($cart, $amountPaid, $customerId, $accountId, $discountAmount) {
            $subTotal = collect($cart)->sum('row_total');
            $grandTotal = max(0, $subTotal - $discountAmount);


            $actualPaid = min($amountPaid, $grandTotal);
            $account = Account::lockForUpdate()->find($accountId);

            $paymentStatus = match (true) {
                $account->type === 'credit_receivable' => 'credit',
                $actualPaid >= $grandTotal => 'paid',
                $actualPaid > 0 => 'partial',
                default => 'credit',
            };

           $invoice = Invoice::create([
                'customer_id'    => $customerId,
                'invoice_date'   => now(),
                'status'         => 'delivered',
                'payment_method' => $account->name,
                'account_id'     => $accountId,
                'total_amount'   => $grandTotal,
                'amount_paid'    => $actualPaid,
                'payment_status' => $paymentStatus,
            ]);

            $totalCost = 0;

            foreach ($cart as $item) {
                $invoice->items()->create([
                    'sellable_type' =>$item['type'],
                    'sellable_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'row_total' => $item['row_total'],

                ]);

                $totalCost += (float) $item['unit_cost'] * (float) $item['quantity'];

                $product = $item['type']::find($item['id']);
                if($product){
                    if($product->current_stock < $item['quantity']) {
                        Notification::make()->title("Insufficient stock for {$product->name}")
                            ->warning()
                            ->send();
                    }
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            $invoice->updateQuietly(['total_cost' => $totalCost]);

            if($actualPaid > 0 ) {
                $profit = max(0, $actualPaid - $totalCost);

                $invoice->transactions()->create([
                    'account_id' => $account->id,
                    'type' => 'in', 
                    'amount' => $actualPaid,
                    'description' => "Sale: Invoice #{$invoice->id}",
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $actualPaid);
                $account->increment('capital_pool', min($actualPaid, $totalCost));
                $account->increment('profit_pool', $profit);
            }

            return $invoice;
        });
    }
}
