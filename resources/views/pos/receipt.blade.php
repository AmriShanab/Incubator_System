<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $invoice->id }}</title>
    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        /* THERMAL PRINTER SPECIFIC CSS */
        @page {
            margin: 0;
            size: 80mm auto; /* Standard Thermal Width */
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace; /* Best readability on thermal */
            font-size: 12px;
            color: #000;
            background: #f4f4f4; /* Gray background for screen preview only */
        }

        .receipt-container {
            width: 72mm; /* Leave a small margin inside the 80mm paper */
            margin: 0 auto;
            padding: 15px 4mm;
            background: #fff;
        }

        /* Helpers */
        .center { text-align: center; }
        .left   { text-align: left; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        
        /* Store Header */
        .logo {
            max-width: 140px;
            max-height: 80px;
            margin-bottom: 5px;
            /* Use a black & white or grayscale logo for best thermal results */
        }
        .store-name {
            font-family: 'Arial', sans-serif; /* Bold sans-serif for store name */
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .store-address {
            font-size: 11px;
            line-height: 1.3;
            color: #333;
        }

        /* Dividers */
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .divider-solid { border-bottom: 1px solid #000; margin: 10px 0; }

        /* Meta Info (Date, Inv, Customer) */
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 4px;
        }

        /* Items Table */
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { 
            border-bottom: 1px solid #000; 
            padding-bottom: 5px; 
            font-size: 11px; 
            text-transform: uppercase; 
        }
        th, td { padding: 4px 0; vertical-align: top; }
        
        .col-item  { width: 50%; padding-right: 5px; }
        .col-qty   { width: 15%; text-align: center; }
        .col-price { width: 35%; text-align: right; }
        
        .item-name {
            display: block;
            font-weight: bold;
            font-size: 11.5px;
        }
        .item-sub {
            display: block;
            font-size: 10px;
            color: #444;
        }

        /* Totals Area */
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 3px 0;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            padding: 6px 0;
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        /* Barcode Area */
        .barcode-container {
            margin-top: 15px;
            text-align: center;
        }
        .barcode-container svg {
            max-width: 100%;
            height: auto;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 15px;
            line-height: 1.4;
        }
        .footer-tag {
            font-size: 9px;
            margin-top: 8px;
            display: block;
        }

        /* Screen-only Print Controls */
        .no-print {
            text-align: center;
            padding: 15px;
            background: #333;
            color: white;
            margin-bottom: 10px;
        }
        .no-print button {
            padding: 8px 16px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            background: #22c55e;
            color: white;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .no-print button.secondary { background: #555; }
        .no-print button:hover { opacity: 0.9; }

        @media print {
            .no-print { display: none; }
            body { background: #fff; }
            .receipt-container { width: 100%; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <button onclick="window.close()" class="secondary">Close Window</button>
    </div>

    @php
        // Resolve Barcode Value (Tracking priority, fallback to Invoice ID)
        $invString = 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
        $barcodeValue = $invoice->tracking_number ? $invoice->tracking_number : $invString;
    @endphp

    <div class="receipt-container">
        
        <div class="center">
            <img src="{{ asset('img/logo.png') }}" alt="Store Logo" class="logo" onerror="this.style.display='none'">
            
            <div class="store-name">SN TECH</div>
            <div class="store-address">
                No:5/19 Poles Road Left (IBM complex) Puttalam<br>
                Sri Lanka | Tel: 0761023168 0776969467
            </div>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span><b>Date:</b> {{ $invoice->created_at->format('d/m/Y H:i') }}</span>
            <span><b>Inv:</b> #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        
        @if($invoice->customer && $invoice->customer->name !== 'Walk-in Customer')
            <div class="info-row" style="margin-top: 3px;">
                <span><b>To:</b> {{ $invoice->customer->name }}</span>
                <span>{{ $invoice->customer->phone ?? '' }}</span>
            </div>
        @endif

        <div class="divider-solid"></div>

        <table>
            <thead>
                <tr>
                    <th class="left col-item">Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-price">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="col-item">
                        <span class="item-name">{{ $item->sellable->name ?? 'Unknown Item' }}</span>
                        <span class="item-sub">@ LKR {{ number_format($item->unit_price, 2) }}</span>
                    </td>
                    <td class="col-qty bold">{{ $item->quantity }}</td>
                    <td class="col-price bold">{{ number_format($item->row_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @php
            // Calculate if a global discount was applied
            $subtotal = $invoice->items->sum('row_total');
            $discount = $subtotal - $invoice->total_amount;
        @endphp

        @if($discount > 0)
            <div class="totals-row" style="margin-top: 5px;">
                <span>Subtotal:</span>
                <span>LKR {{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="totals-row" style="color: #555;">
                <span>Discount Applied:</span>
                <span>- LKR {{ number_format($discount, 2) }}</span>
            </div>
        @endif

        <div class="totals-row grand-total">
            <span>TOTAL DUE</span>
            <span>LKR {{ number_format($invoice->total_amount, 2) }}</span>
        </div>

        @if($invoice->amount_paid > 0)
            <div class="totals-row">
                <span>Amount Paid:</span>
                <span>LKR {{ number_format($invoice->amount_paid, 2) }}</span>
            </div>
            @if($invoice->total_amount - $invoice->amount_paid > 0)
            <div class="totals-row bold">
                <span>Balance Due:</span>
                <span>LKR {{ number_format($invoice->total_amount - $invoice->amount_paid, 2) }}</span>
            </div>
            @endif
        @endif

        <div class="divider"></div>

        <div class="barcode-container">
            <svg id="barcode"></svg>
        </div>

        <div class="footer">
            <b>Thank you for your business!</b><br>
            Please keep this receipt for your records.<br>
            <span class="footer-tag">System powered by Wii</span>
        </div>

        <div style="height: 15mm;"></div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Generate Barcode
            JsBarcode("#barcode", "{{ $barcodeValue }}", {
                format: "CODE128",
                width: 1.8,         // Thickness of bars
                height: 45,         // Height of barcode
                displayValue: true, // Show text below
                fontSize: 13,
                fontOptions: "bold",
                margin: 0
            });

            // Auto trigger print dialogue after a tiny delay to ensure barcode paints
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>

</body>
</html>