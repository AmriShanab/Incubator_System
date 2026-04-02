<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    // 1. Assign correct payment status BEFORE saving to DB
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Dynamically set payment status based on the selected Account
        $account = Account::find($data['account_id']);
        
        if ($account) {
            $accountName = strtolower($account->name);
            
            if (str_contains($accountName, 'accounts receivable') || str_contains($accountName, 'credit')) {
                // It is a Credit Sale
                $data['amount_paid'] = 0;
                $data['payment_status'] = 'credit';
            } else {
                // It is Cash, Bank, or COD. 
                $data['amount_paid'] = $data['total_amount'] ?? 0;
                $data['payment_status'] = 'paid';
            }
        }

        return $data;
    }

    // 2. Distribute funds and update stock AFTER saving to DB
    protected function afterCreate(): void
    {
        $invoice = $this->record;

        DB::transaction(function () use ($invoice) {
            
            // ─── PART A: CALCULATE REAL COST ────────────────────────────
            // Because Filament removes relationship arrays from the main data 
            // before creation, we MUST read it from the raw form data array: $this->data
            $rawItems = $this->data['items'] ?? [];
            $totalCost = 0;
            
            foreach ($rawItems as $item) {
                $totalCost += (float) ($item['unit_cost'] ?? 0) * (int) ($item['quantity'] ?? 1);
            }

            // Update the invoice with the accurate cost we just calculated
            $invoice->updateQuietly(['total_cost' => $totalCost]);

            // ─── PART B: FINANCIAL ROUTING ──────────────────────────────
            $account = clone $invoice->account; 

            if ($account && $invoice->total_amount > 0) {
                $amount = (float) $invoice->total_amount;
                $cost   = $totalCost; // Use our newly calculated accurate cost
                $profit = max(0, $amount - $cost);

                // Log the specific transaction to the EXACT account chosen
                $invoice->transactions()->create([
                    'account_id'       => $account->id,
                    'type'             => 'in',
                    'amount'           => $amount,
                    'description'      => "Sale recorded via Invoice #{$invoice->id}",
                    'transaction_date' => now()->toDateString(),
                ]);

                // Increment the physical account balances, split perfectly into Capital and Profit
                $account->increment('balance', $amount);
                $account->increment('capital_pool', min($amount, $cost));
                $account->increment('profit_pool', $profit);
            }

            // ─── PART C: INVENTORY MANAGEMENT ───────────────────────────
            // Force reload items to guarantee we are working with the saved DB rows
            $invoice->load('items.sellable');

            foreach ($invoice->items as $item) {
                $product = $item->sellable;

                if ($product) {
                    if ($product->current_stock < $item->quantity) {
                        Notification::make()
                            ->title("Warning: Negative Stock for {$product->name}")
                            ->warning()
                            ->send();
                    }

                    $product->decrement('current_stock', $item->quantity);
                }
            }
        });
    }

    // Redirect back to the table after saving
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}