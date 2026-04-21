@extends('layouts.super_admin')

@section('content')
    <a href="{{ route('superadmin.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800 mb-6 inline-block">&larr; Back to Dashboard</a>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900">Invoice #{{ $invoice->id }}</h2>
                        <p class="text-slate-500 text-sm">Created on: {{ $invoice->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <span class="bg-slate-100 text-slate-800 font-bold px-3 py-1 rounded border border-slate-300 uppercase text-xs">{{ $invoice->status }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm mb-6">
                    <div class="p-4 bg-slate-50 rounded border border-slate-200">
                        <span class="block text-slate-500 font-semibold mb-1">Customer</span>
                        <span class="font-bold text-slate-900">{{ $invoice->customer->name ?? 'Unknown' }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded border border-slate-200">
                        <span class="block text-slate-500 font-semibold mb-1">Payment Status</span>
                        <span class="font-bold text-slate-900">{{ ucfirst($invoice->payment_status) }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded border border-slate-200">
                        <span class="block text-slate-500 font-semibold mb-1">Total Amount</span>
                        <span class="font-mono font-bold text-slate-900">LKR {{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded border border-slate-200">
                        <span class="block text-slate-500 font-semibold mb-1">Target Account</span>
                        <span class="font-bold text-slate-900">{{ $invoice->account->name ?? 'None' }}</span>
                    </div>
                </div>

                <h3 class="font-bold text-slate-800 mb-3 border-b pb-2">Items to Restock</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    @foreach($invoice->items as $item)
                        <li class="flex justify-between">
                            <span>{{ $item->quantity }}x {{ $item->sellable->name ?? 'Item' }}</span>
                            <span class="font-mono">LKR {{ number_format($item->row_total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="md:col-span-1">
            <div class="bg-red-50 p-6 rounded-lg shadow-sm border-2 border-red-600" x-data="{ confirmation: '' }">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h3 class="text-xl font-black text-red-800 uppercase tracking-wide">Danger Zone</h3>
                </div>
                
                <p class="text-red-900 text-sm mb-4 font-medium">
                    Reverting this invoice will permanently execute the following actions:
                </p>
                <ul class="list-disc list-inside text-sm text-red-800 mb-6 space-y-1 font-semibold">
                    <li>Deduct LKR {{ number_format($invoice->amount_paid, 2) }} from {{ $invoice->account->name ?? 'account' }}</li>
                    <li>Revert capital and profit pools.</li>
                    <li>Return {{ $invoice->items->sum('quantity') }} items to inventory.</li>
                    <li>Mark invoice status as VOIDED.</li>
                </ul>

                <form action="{{ route('superadmin.invoices.execute-revert', $invoice->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-red-900 mb-2">
                            Type <span class="bg-red-200 px-1 font-mono">REVERT-{{ $invoice->id }}</span> to confirm:
                        </label>
                        <input type="text" x-model="confirmation" autocomplete="off" class="w-full rounded border-red-300 focus:ring-red-500 focus:border-red-500 text-center font-mono uppercase font-bold text-red-900">
                    </div>

                    <button type="submit" 
                            :disabled="confirmation !== 'REVERT-{{ $invoice->id }}'"
                            :class="{'opacity-50 cursor-not-allowed': confirmation !== 'REVERT-{{ $invoice->id }}'}"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-3 rounded uppercase tracking-wider transition disabled:bg-slate-400">
                        Execute Reversal
                    </button>
                </form>
            </div>
        </div>

    </div>
@endsection