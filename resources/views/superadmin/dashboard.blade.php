@extends('layouts.super_admin')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900">System Operations Dashboard</h1>
        <p class="text-slate-500 mt-1">Select an entity to perform override actions.</p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Locate Invoice for Reversal</h2>
        <form action="{{ route('superadmin.invoices.search') }}" method="GET" class="flex gap-4">
            <input type="text" name="query" placeholder="Enter Invoice ID or Tracking Number..." class="flex-1 rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 px-4 py-2 border">
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-md font-semibold hover:bg-slate-700 transition">Fetch Record</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="font-bold text-slate-800">Recent Admin Overrides</h3>
        </div>
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Target ID</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200 text-sm">
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">2026-04-21 10:15 AM</td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">VOIDED INVOICE</span></td>
                    <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-900">#10244</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection