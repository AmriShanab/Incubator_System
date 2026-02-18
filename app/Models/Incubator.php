<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incubator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'current_stock', // Ensure this is here
        'price',         // Ensure this is here
    ];

    // This is the correct BOM relationship
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'incubator_material')
            ->withPivot('quantity_required')
            ->withTimestamps();
    }

    public function invoiceItems()
    {
        return $this->morphMany(InvoiceItem::class, 'sellable');
    }

    public function adjustments()
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }
}