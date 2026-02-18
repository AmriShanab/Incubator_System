<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'adjustable_id',
        'adjustable_type',
        'quantity',
        'type',
        'reason',
        'adjustment_date',
    ];

    public function adjustable()
    {
        return $this->morphTo();
    }
}
