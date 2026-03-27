<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $invoice->id }}</title>
    <style>
        /* THERMAL PRINTER SPECIFIC CSS */
        @page {
            margin: 0; /* Removes browser header/footer */
        }
        body {
            margin: 0;
            padding: 10px;
            font-family: 'Courier New', Courier, monospace; /* Best for thermal */
            font-size: 12px;
            color: #000;
            width: 80mm; /* Standard Thermal Paper Width */
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 5px 0; }
        .flex { display: flex; justify-content: space-between; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 0; }
        .text-right { text-align: right; }

        /* Hide elements on screen that should only be printed */
        .no-print { display: block; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print center">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close Window</button>
    </div>

    <div class="center bold" style="font-size: 16px;">
        SN INCUBATORS<br>
        <span style="font-size: 12px; font-weight: normal;">Puttalam, Sri Lanka</span>
    </div>

    <div class="divider"></div>

    <div class="flex">
        <span>Inv: #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</span>
        <span>{{ $invoice->created_at->format('d-M-Y H:i') }}</span>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td style="max-width: 40mm; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $item->sellable->name ?? 'Unknown Item' }}
                </td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->row_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="flex bold" style="font-size: 14px;">
        <span>TOTAL DUE:</span>
        <span>LKR {{ number_format($invoice->total_amount, 2) }}</span>
    </div>

    <div class="divider"></div>
    
    <div class="center" style="margin-top: 10px;">
        Thank you for your business!<br>
        <span style="font-size: 10px;">Software by Wii</span>
    </div>

    <div style="height: 30px;"></div>

</body>
</html>