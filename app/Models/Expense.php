<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    protected $fillable = [
        'account_id',
        'category',
        'amount',
        'description',
        'expense_date',
        'pool_type', // New field for capital/profit classification
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
        static::created(function ($expense) {
            DB::transaction(function () use ($expense) {
                // 1. Log the transaction to the ledger
                $expense->transactions()->create([
                    'account_id' => $expense->account_id,
                    'type' => 'out',
                    'amount' => $expense->amount,
                    'description' => "Expense ({$expense->category}): " . ($expense->description ?? 'N/A'),
                    'transaction_date' => $expense->expense_date,
                ]);

                // 2. Deduct physical balance
                $expense->account->decrement('balance', $expense->amount);

                // 3. Deduct from the specific virtual pool
                if ($expense->pool_type === 'capital') {
                    $expense->account->decrement('capital_pool', $expense->amount);
                } else {
                    $expense->account->decrement('profit_pool', $expense->amount);
                }
            });
        });

        static::deleted(function ($expense) {
            DB::transaction(function () use ($expense) {
                $expense->account->increment('balance', $expense->amount);

                if ($expense->pool_type === 'capital') {
                    $expense->account->increment('capital_pool', $expense->amount);
                } else {
                    $expense->account->increment('profit_pool', $expense->amount);
                }

                // Remove the transaction log
                $expense->transactions()->delete();
            });
        });
    }
}
