<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'material_id',
        'quantity',
        'unit_cost',
        'row_total',

    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
