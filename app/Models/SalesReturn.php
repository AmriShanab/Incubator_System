<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'invoice_id',
        'return_date',
        'reason',
        'refund_amount',
        'status'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
