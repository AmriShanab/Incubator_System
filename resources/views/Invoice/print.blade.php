<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .details { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .total { text-align: right; font-weight: bold; font-size: 1.2em; margin-top: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>INVOICE</h1>
            <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
        </div>
        <div style="text-align: right;">
            <h3>Incubator Co.</h3> <p>123 Factory Road, Sri Lanka</p>
        </div>
    </div>

    <div class="details">
        <h3>Bill To:</h3>
        <p><strong>{{ $invoice->customer->name }}</strong></p>
        <p>{{ $invoice->customer->phone }}</p>
        <p>{{ $invoice->customer->address }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->sellable->name ?? 'Unknown Item' }}</td>
                <td>{{ class_basename($item->sellable_type) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->row_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Grand Total: LKR {{ number_format($invoice->total_amount, 2) }}
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Invoice</button>
    </div>
</body>
</html>