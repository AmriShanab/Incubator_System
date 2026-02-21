<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['customer_id', 'invoice_date', 'status', 'total_amount', 'payment_method'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }
}
