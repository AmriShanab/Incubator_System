<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cost_price',
        'selling_price',
        'current_stock',
    ];

    public function invoiceItems()
    {
        return $this->morphMany(InvoiceItem::class, 'sellable');
    }

    public function adjustments()
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }
}
