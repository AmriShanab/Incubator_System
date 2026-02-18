<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoice/{invoice}/print', function (Invoice $invoice) {
    $invoice->load(['customer', 'items.sellable']);
    return view('invoice.print', compact('invoice'));
})->name('invoice.print');
