<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import this!

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'incubator_id',
        'quantity_sold',
        'total_price',
        'sold_at',
    ];

    // ADD THIS METHOD
    public function incubator(): BelongsTo
    {
        return $this->belongsTo(Incubator::class);
    }
}