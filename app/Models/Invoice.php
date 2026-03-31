<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_date',
        'status',
        'tracking_number',
        'total_amount',
        'total_cost',     
        'total_profit',   
        'payment_method',
        'account_id',
        'is_settled',
        'amount_paid',
        'payment_status',   
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'reference');
    }

    protected static function booted()
    {
        static::saving(function ($invoice) {
            if ($invoice->account_id) {
                $accountName = Account::query()->whereKey($invoice->account_id)->value('name');

                if ($accountName) {
                    $invoice->payment_method = $accountName;
                }
            }
        });

        static::updated(function ($invoice) {
            if ($invoice->isDirty('status') && $invoice->status === 'delivered') {

                $totalCost = $invoice->items->sum(fn($item) => $item->unit_cost * $item->quantity);
                $totalProfit = $invoice->total_amount - $totalCost;

                // Save it to the invoice history
                $invoice->updateQuietly([
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                ]);

                // PHASE 3: SPLIT PAYMENT LOGIC
                if ($invoice->total_amount > 0 && !$invoice->transactions()->exists()) {

                    DB::transaction(function () use ($invoice, $totalCost, $totalProfit) {
                        
                        $amountPaid = (float) $invoice->amount_paid;
                        $dueAmount = max(0, (float) $invoice->total_amount - $amountPaid);

                        // --- 1. CASH/BANK LOGIC (Money actually received today) ---
                        if ($amountPaid > 0 && $invoice->account_id) {
                            
                            // "Capital First" Allocation: Prioritize recovering costs before declaring profit
                            $cashCapital = min($amountPaid, $totalCost);
                            $cashProfit = max(0, $amountPaid - $totalCost);

                            $invoice->transactions()->create([
                                'account_id' => $invoice->account_id,
                                'type' => 'in',
                                'amount' => $amountPaid,
                                'description' => "Partial/Full payment received for Invoice #{$invoice->id}",
                                'transaction_date' => now()->toDateString(),
                            ]);

                            $invoice->account->increment('balance', $amountPaid);
                            $invoice->account->increment('capital_pool', $cashCapital); 
                            $invoice->account->increment('profit_pool', $cashProfit);
                        }

                        // --- 2. ACCOUNTS RECEIVABLE LOGIC (Money owed as credit) ---
                        if ($dueAmount > 0) {
                            // Automatically find or create the AR account so the system never crashes
                            $arAccount = Account::firstOrCreate(
                                ['name' => 'Accounts Receivable'],
                                ['balance' => 0, 'capital_pool' => 0, 'profit_pool' => 0]
                            );

                            // Calculate how much capital and profit is locked inside this customer debt
                            $cashCapital = min($amountPaid, $totalCost); 
                            $cashProfit = max(0, $amountPaid - $totalCost);
                            
                            $arCapital = max(0, $totalCost - $cashCapital); 
                            $arProfit = max(0, $totalProfit - $cashProfit); 

                            $invoice->transactions()->create([
                                'account_id' => $arAccount->id,
                                'type' => 'in',
                                'amount' => $dueAmount,
                                'description' => "Credit issued for Invoice #{$invoice->id}",
                                'transaction_date' => now()->toDateString(),
                            ]);

                            $arAccount->increment('balance', $dueAmount);
                            $arAccount->increment('capital_pool', $arCapital);
                            $arAccount->increment('profit_pool', $arProfit);
                        }
                    });
                }
            }
        });
    }
}