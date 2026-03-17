<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FundTransfer extends Model
{
    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'pool_type', // Added this!
        'transfer_date',
        'reference_note'
    ];

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    protected static function booted()
    {
        static::created(function ($transfer) {
            DB::transaction(function () use ($transfer) {
                
                $source = $transfer->fromAccount;
                $destination = $transfer->toAccount;

                $source->decrement('balance', $transfer->amount);
                $destination->increment('balance', $transfer->amount);

                if ($transfer->pool_type === 'profit') {
                    $source->decrement('profit_pool', $transfer->amount);
                    $destination->increment('profit_pool', $transfer->amount);
                } else {
                    $source->decrement('capital_pool', $transfer->amount);
                    $destination->increment('capital_pool', $transfer->amount);
                }
            });
        });
    }
}