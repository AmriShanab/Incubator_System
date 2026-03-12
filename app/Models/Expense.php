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
                $expense->transactions()->create([
                    'account_id' => $expense->account_id,
                    'type' => 'out',
                    'amount' => $expense->amount,
                    'description' =>$expense->description,
                    'transaction_date' => $expense->expense_date,
                ]);

                $expense->account->decrement('balance', $expense->amount);
            });
        });

        static::deleted(function ($expense) {
            DB::transaction(function () use ($expense) {
                $expense->transactions()->delete();
                $expense->account->increment('balance', $expense->amount);
            });
        });
    }
}
