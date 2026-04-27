<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchasable_type', // Replaced material_id with these two
        'purchasable_id',
        'quantity',
        'unit_cost',
        'row_total',

    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function purchasable()
    {
        return $this->morphTo();
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
