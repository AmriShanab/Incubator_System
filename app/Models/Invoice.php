<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $fillable = ['customer_id', 'invoice_date', 'status', 'total_amount', 'payment_method'];

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
        static::updated(function ($invoice) {
            if ($invoice->isDirty('status') && $invoice->status === 'delivered') {

                if ($invoice->account_id && $invoice->total_amount > 0 && !$invoice->transactions()->exists()) {

                    DB::transaction(function () use ($invoice) {
                        $invoice->transactions()->create([
                            'account_id' => $invoice->account_id,
                            'type' => 'in',
                            'amount' => $invoice->total_amount,
                            'description' => "Payment received for Invoice #{$invoice->id}",
                            'transaction_date' => now()->toDateString(),
                        ]);

                        $invoice->account->increment('balance', $invoice->total_amount);
                    });
                }
            }
        });
    }
}
