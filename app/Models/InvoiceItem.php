<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'sellable_type', 'sellable_id', 'quantity', 'unit_price', 'row_total'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    
    // The magic link to both tables
    public function sellable() { return $this->morphTo(); }
}
