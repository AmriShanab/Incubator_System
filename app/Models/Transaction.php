<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable= [
        'account_id',
        'type',
        'amount', 
        'description',
        'reference_type',
        'reference_id',
        'transaction_date',
        'invoice_id', 
    ];

    public function account() : BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reference() : MorphTo
    {
        return $this->morphTo();
    }

    public function Invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
