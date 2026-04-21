<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class SystemOpsController extends Controller
{
    public function dashboard()
    {
        return view('superadmin.dashboard');
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if(!$query) {
            return back()->with('error', 'Please enter a Invoice ID.');
        }

        $invoice = Invoice::where('id', $query)->orWhere('tracking_number', $query)->first();

        if(!$invoice) {
            return redirect()->back()->with('error', 'No invoice found with the provided ID or tracking number.');
        }

        if($invoice->status === 'voided'){
            return redirect()->back()->with('error', 'The invoice you are trying to access has been voided and cannot be viewed.');
        }

        return redirect()->route('superadmin.invoices.show-revert', $invoice->id);
    }

    public function showRevert($id)
    {
        $invoice = Invoice::with(['items.sellable', 'customer', 'account'])->findOrFail($id);
        return view('superadmin.invoices_revert', compact('invoice'));
    }
}
