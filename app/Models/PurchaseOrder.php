<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'account_id',
        'order_date',
        'status',
        'total_amount'
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
        static::updated(function ($po) {
            if ($po->isDirty('status') && $po->status === 'received') {
                if ($po->account_id && $po->total_amount > 0 && !$po->transactions()->exists()) {
                    DB::transaction(function () use ($po) {
                        $po->transactions()->create([
                            'account_id' => $po->account_id,
                            'type' => 'out',
                            'amount' => $po->total_amount,
                            'description' => "Payment sent for Purchase Order #{$po->id} to Supplier",
                            'transaction_date' => now()->toDateString(),
                        ]);
                        $po->account->decrement('balance', $po->total_amount);
                        $po->account->decrement('capital_pool', $po->total_amount);
                    });
                }
            }
        });
    }
}
