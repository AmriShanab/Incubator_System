<?php

use App\Http\Controllers\SuperAdmin\AuthController;
use App\Http\Controllers\SuperAdmin\InvoiceReversionController;
use App\Http\Controllers\SuperAdmin\SystemOpsController;
use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/invoice/{invoice}/print', function (Invoice $invoice) {
    $invoice->load(['customer', 'items.sellable']);
    return view('invoice.print', compact('invoice'));
})->name('invoice.print');


Route::get('/pos/receipt/{invoice}', function (Invoice $invoice) {
    $invoice->load('items');
    return view('pos.receipt', compact('invoice'));
})->name('pos.receipt');


Route::prefix('system-ops')->group(function () {
    
    // Public Login Routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('superadmin.login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('superadmin.login.submit');

    // Protected Routes (Requires Custom SuperAdminAccess Middleware)
    Route::middleware(['super.admin'])->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout'])->name('superadmin.logout');
        
        Route::get('/dashboard', [SystemOpsController::class, 'dashboard'])->name('superadmin.dashboard');
        Route::get('/search', [SystemOpsController::class, 'search'])->name('superadmin.invoices.search');
        
        Route::get('/invoices/{id}/revert', [SystemOpsController::class, 'showRevert'])->name('superadmin.invoices.show-revert');
        Route::post('/invoices/{id}/execute-revert', [InvoiceReversionController::class, 'revertInvoice'])->name('superadmin.invoices.execute-revert');
        
    });
});