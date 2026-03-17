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
        'total_amount',
        'total_cost',     // <--- Add this!
        'total_profit',   // <--- Add this!
        'payment_method',
        'account_id'
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

                if ($invoice->account_id && $invoice->total_amount > 0 && !$invoice->transactions()->exists()) {

                    DB::transaction(function () use ($invoice, $totalCost, $totalProfit) {
                        $invoice->transactions()->create([
                            'account_id' => $invoice->account_id,
                            'type' => 'in',
                            'amount' => $invoice->total_amount,
                            'description' => "Payment received for Invoice #{$invoice->id}",
                            'transaction_date' => now()->toDateString(),
                        ]);

                        $invoice->account->increment('balance', $invoice->total_amount);
                        $invoice->account->increment('capital_pool', $totalCost); // FIXED SPELLING HERE
                        $invoice->account->increment('profit_pool', $totalProfit);
                    });
                }
            }
        });
    }
}
