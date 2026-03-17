<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Income extends Model
{
    protected $fillable = [
        'account_id',
        'title',
        'amount',
        'pool_type',
        'description',
        'income_date'
    ];

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
        static::created(function ($income) {
            DB::transaction(function () use ($income) {
                // 1. Log the transaction to the ledger
                $income->transactions()->create([
                    'account_id' => $income->account_id,
                    'type' => 'in',
                    'amount' => $income->amount,
                    'description' => "Manual Income: {$income->title}",
                    'transaction_date' => $income->income_date,
                ]);

                // 2. Add to the physical account balance
                $income->account->increment('balance', $income->amount);

                // 3. Add to the specific virtual pool they selected
                if ($income->pool_type === 'capital') {
                    $income->account->increment('capital_pool', $income->amount);
                } else {
                    $income->account->increment('profit_pool', $income->amount);
                }
            });
        });
    }
}