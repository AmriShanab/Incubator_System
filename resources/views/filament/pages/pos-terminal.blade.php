<x-filament-panels::page>

    {{-- ================================================================
     POS TERMINAL — Bottom Dock Layout, System-Aware Light/Dark
     Keyboard: / = search | 1–9 = quick-add | Enter = complete
               Esc = undo last | T = focus tender
     ================================================================ --}}

    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap');

        /* ── Strip Filament page chrome ── */
        .fi-main-ctn {
            overflow: hidden !important;
        }

        .fi-page {
            padding: 0 !important;
        }

        .fi-page>.fi-page-header {
            display: none !important;
        }

        /* ── Scoped reset ── */
        #pos-root,
        #pos-root *,
        #pos-root *::before,
        #pos-root *::after {
            box-sizing: border-box !important;
            margin: 0;
            padding: 0;
        }

        /* ════════════════════════════════════════
   DESIGN TOKENS — light / dark
════════════════════════════════════════ */
        #pos-root {
            --bg: #eef0f3;
            --s0: #ffffff;
            --s1: #f5f6f8;
            --s2: #ecedf0;
            --s3: #e2e3e8;
            --t0: #0f1014;
            --t1: #5a5e6b;
            --t2: #9296a3;
            --b0: #dddfe4;
            --b1: #c8cad1;
            --green: #16a34a;
            --green-s: #f0fdf4;
            --green-d: #bbf7d0;
            --amber: #92400e;
            --amber-s: #fffbeb;
            --red: #b91c1c;
            --red-s: #fef2f2;
            --blue: #1d4ed8;
            --blue-s: #eff6ff;
            --r-sm: 8px;
            --r-md: 12px;
            --r-lg: 16px;
            --r-xl: 20px;
            --r-pill: 9999px;
            --sh-sm: 0 1px 2px rgba(0, 0, 0, .06), 0 1px 3px rgba(0, 0, 0, .04);
            --sh-md: 0 4px 12px rgba(0, 0, 0, .08), 0 2px 4px rgba(0, 0, 0, .04);
            --sh-lg: 0 8px 28px rgba(0, 0, 0, .10), 0 2px 8px rgba(0, 0, 0, .06);
            --sh-up: 0 -4px 24px rgba(0, 0, 0, .06);
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--t0);
            height: calc(100dvh - 57px);
            min-height: 600px;
            overflow: hidden;
        }

        .dark #pos-root {
            --bg: #0a0a0c;
            --s0: #111113;
            --s1: #18181b;
            --s2: #1f1f23;
            --s3: #27272c;
            --t0: #f2f2f4;
            --t1: #86868f;
            --t2: #52525a;
            --b0: #232327;
            --b1: #303036;
            --green: #22c55e;
            --green-s: #052e16;
            --green-d: #166534;
            --amber: #f59e0b;
            --amber-s: #1a1000;
            --red: #f87171;
            --red-s: #1a0606;
            --blue: #60a5fa;
            --blue-s: #0d1f3c;
            --sh-sm: 0 1px 3px rgba(0, 0, 0, .5);
            --sh-md: 0 4px 16px rgba(0, 0, 0, .6);
            --sh-lg: 0 8px 30px rgba(0, 0, 0, .7);
            --sh-up: 0 -4px 24px rgba(0, 0, 0, .3);
        }

        /* ── Main Layout: Vertical Stack ── */
        #pos-root .pos-wrap {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* ══════════════════════════════════════
   TOP — Product Browser
══════════════════════════════════════ */
        #pos-root .pos-top-panel {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: var(--bg);
        }

        #pos-root .pos-toolbar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 24px !important;
            background: var(--s0);
            border-bottom: 1px solid var(--b0);
            flex-shrink: 0;
        }

        #pos-root .pos-search-wrap {
            position: relative;
            flex: 1;
        }

        #pos-root .pos-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--t2);
            pointer-events: none;
        }

        #pos-root .pos-search {
            width: 100%;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 14px 16px 14px 46px !important;
            border-radius: var(--r-md);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        #pos-root .pos-search::placeholder {
            color: var(--t2);
        }

        #pos-root .pos-search:focus {
            border-color: var(--green);
            background: var(--s0);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .12);
        }

        #pos-root .pos-kbd-tag {
            background: var(--s2);
            border: 1px solid var(--b1);
            border-radius: var(--r-sm);
            padding: 8px 12px !important;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t2);
            white-space: nowrap;
            flex-shrink: 0;
        }

        #pos-root .pos-cats {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 24px !important;
            background: var(--s0);
            border-bottom: 1px solid var(--b0);
            overflow-x: auto;
            flex-shrink: 0;
        }

        #pos-root .pos-cats::-webkit-scrollbar {
            display: none;
        }

        #pos-root .pos-cat {
            padding: 10px 20px !important;
            border-radius: var(--r-pill);
            border: 1.5px solid var(--b1);
            background: transparent;
            color: var(--t1);
            font-size: 13.5px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            white-space: nowrap;
            transition: all .12s;
            line-height: 1.4;
        }

        #pos-root .pos-cat:hover {
            background: var(--s2);
            color: var(--t0);
            border-color: var(--b0);
        }

        #pos-root .pos-cat.active {
            background: var(--green-s);
            border-color: var(--green-d);
            color: var(--green);
        }

        #pos-root .pos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px !important;
            padding: 24px !important;
            overflow-y: auto;
            flex: 1;
            align-content: start;
        }

        #pos-root .pos-grid::-webkit-scrollbar {
            width: 6px;
        }

        #pos-root .pos-grid::-webkit-scrollbar-track {
            background: transparent;
        }

        #pos-root .pos-grid::-webkit-scrollbar-thumb {
            background: var(--b1);
            border-radius: 6px;
        }

        #pos-root .pos-card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 18px !important;
            min-height: 120px;
            background: var(--s0);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-lg);
            cursor: pointer;
            text-align: left;
            font-family: 'DM Sans', sans-serif;
            box-shadow: var(--sh-sm);
            transition: border-color .12s, box-shadow .12s, transform .08s, background .1s;
            width: 100%;
        }

        #pos-root .pos-card:hover:not(:disabled) {
            border-color: var(--green);
            box-shadow: var(--sh-md);
        }

        #pos-root .pos-card:active:not(:disabled) {
            transform: scale(0.95);
        }

        #pos-root .pos-card:disabled {
            opacity: .35;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        #pos-root .pos-card-stock {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px !important;
            border-radius: var(--r-sm);
            background: var(--s2);
            color: var(--t2);
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.4;
        }

        #pos-root .pos-card-stock.ok {
            background: var(--green-s);
            color: var(--green);
        }

        #pos-root .pos-card-stock.low {
            background: var(--amber-s);
            color: var(--amber);
        }

        #pos-root .pos-card-name {
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.4;
            color: var(--t0);
            padding-right: 36px !important;
        }

        #pos-root .pos-card-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            font-weight: 700;
            color: var(--green);
            margin-top: auto;
        }

        #pos-root .pos-card-num {
            position: absolute;
            bottom: 12px;
            right: 12px;
            font-size: 10px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t2);
            opacity: .7;
        }

        #pos-root .pos-empty {
            grid-column: 1/-1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 60px 0 !important;
            color: var(--t2);
            font-size: 15px;
        }

        /* ══════════════════════════════════════
   BOTTOM — Checkout Dock (3 Columns)
══════════════════════════════════════ */
        #pos-root .pos-bottom-panel {
            height: 350px;
            flex-shrink: 0;
            display: grid;
            grid-template-columns: minmax(350px, 1.3fr) minmax(280px, 1fr) minmax(280px, 1fr);
            background: var(--s0);
            border-top: 1px solid var(--b0);
            box-shadow: var(--sh-up);
            /* Nice lift effect */
            z-index: 10;
        }

        /* ── Custom chevron arrow for all selects ── */
        #pos-root select.pos-cust-sel,
        #pos-root select.pos-pay-sel,
        #pos-root select.pos-disc-sel {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' viewBox='0 0 24 24'%3E%3Cpath stroke='%239296a3' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
            padding-right: 40px !important;
        }

        /* ── Column 1: Cart ── */
        #pos-root .pos-cart-col {
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--b0);
            background: var(--s0);
        }

        #pos-root .pos-cust {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px !important;
            border-bottom: 1px solid var(--b0);
            background: var(--s1);
            flex-shrink: 0;
        }

        #pos-root .pos-cust-sel {
            flex: 1;
            min-width: 0;
            background-color: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 10px 40px 10px 14px !important;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            cursor: pointer;
            transition: border-color .12s, box-shadow .12s;
        }

        #pos-root .pos-cust-sel:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .1);
        }

        #pos-root .pos-new-btn {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--s0);
            border: 1.5px solid var(--b1);
            color: var(--t1);
            padding: 10px 16px !important;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all .12s;
            white-space: nowrap;
        }

        #pos-root .pos-new-btn:hover {
            background: var(--blue-s);
            border-color: var(--blue);
            color: var(--blue);
        }

        #pos-root .pos-cart {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px !important;
            display: flex;
            flex-direction: column;
            gap: 10px !important;
        }

        #pos-root .pos-cart::-webkit-scrollbar {
            width: 5px;
        }

        #pos-root .pos-cart::-webkit-scrollbar-thumb {
            background: var(--b1);
            border-radius: 5px;
        }

        #pos-root .pos-empty-cart {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--t2);
            font-size: 14px;
        }

        #pos-root .pos-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: center;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-md);
            padding: 12px 16px !important;
            animation: posIn .15s ease;
            transition: border-color .12s;
        }

        #pos-root .pos-item:hover {
            border-color: var(--b1);
        }

        @keyframes posIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #pos-root .pos-item-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--t0);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        #pos-root .pos-item-sub {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 12.5px;
            color: var(--t1);
        }

        #pos-root .pos-price-edit {
            background: var(--s0);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-sm);
            color: var(--green);
            font-size: 12.5px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            width: 85px;
            outline: none;
            padding: 4px 8px !important;
            transition: border-color .12s, box-shadow .12s;
        }

        #pos-root .pos-price-edit:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 2px rgba(34, 197, 94, .15);
        }

        #pos-root .pos-item-ctrl {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #pos-root .pos-qbtn {
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            border: 1.5px solid var(--b1);
            background: var(--s0);
            color: var(--t1);
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-family: 'DM Sans', sans-serif;
            transition: all .1s;
            flex-shrink: 0;
        }

        #pos-root .pos-qbtn:hover {
            background: var(--s2);
            color: var(--t0);
            border-color: var(--t1);
        }

        #pos-root .pos-qbtn.rm {
            color: var(--red);
            border-color: var(--b0);
        }

        #pos-root .pos-qbtn.rm:hover {
            background: var(--red-s);
            border-color: var(--red);
        }

        #pos-root .pos-qty {
            min-width: 28px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t0);
        }

        #pos-root .pos-rtotal {
            min-width: 85px;
            text-align: right;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t0);
        }

        /* ── Column 2: Totals ── */
        #pos-root .pos-totals-col {
            display: flex;
            flex-direction: column;
            padding: 24px !important;
            background: var(--s1);
            border-right: 1px solid var(--b0);
        }

        #pos-root .pos-trow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0 !important;
        }

        #pos-root .pos-tlbl {
            font-size: 14px;
            color: var(--t1);
        }

        #pos-root .pos-tval {
            font-size: 15px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t0);
        }

        #pos-root .pos-disc-wrap {
            display: flex;
            gap: 10px;
            align-items: stretch;
            margin: 16px 0 8px !important;
        }

        #pos-root .pos-disc-sel {
            width: 90px;
            flex-shrink: 0;
            background-color: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 10px 32px 10px 14px !important;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            cursor: pointer;
            transition: border-color .12s;
            background-position: right 10px center;
        }

        #pos-root .pos-disc-sel:focus {
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(234, 179, 8, .1);
        }

        #pos-root .pos-disc-input {
            flex: 1;
            background: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 10px 14px !important;
            border-radius: var(--r-md);
            font-size: 14px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            transition: border-color .12s;
        }

        #pos-root .pos-disc-input::placeholder {
            color: var(--t2);
        }

        #pos-root .pos-disc-input:focus {
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(234, 179, 8, .1);
        }

        #pos-root .pos-disc-badge {
            min-height: 20px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--amber);
            text-align: right;
            padding: 4px 0 0 !important;
            font-weight: 600;
        }

        #pos-root .pos-grand-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-top: auto !important;
            /* Pushes to bottom */
            padding-top: 20px !important;
            border-top: 1.5px solid var(--b0);
        }

        #pos-root .pos-grand-lbl {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--t1);
        }

        #pos-root .pos-grand-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 28px;
            font-weight: 700;
            color: var(--green);
            letter-spacing: -.01em;
        }

        /* ── Column 3: Payment ── */
        #pos-root .pos-pay-col {
            display: flex;
            flex-direction: column;
            padding: 24px !important;
            background: var(--s0);
        }

        #pos-root .pos-pay-sel {
            width: 100%;
            background-color: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 12px 36px 12px 16px !important;
            border-radius: var(--r-md);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            cursor: pointer;
            transition: border-color .12s;
            margin-bottom: 16px !important;
        }

        #pos-root .pos-pay-sel:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .1);
        }

        #pos-root .pos-tender-wrap {
            position: relative;
            margin-bottom: 8px !important;
        }

        #pos-root .pos-tender-pre {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            font-weight: 700;
            color: var(--t2);
            font-family: 'JetBrains Mono', monospace;
            pointer-events: none;
            z-index: 1;
        }

        #pos-root .pos-tender {
            width: 100%;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 16px 16px 16px 54px !important;
            border-radius: var(--r-md);
            font-size: 24px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            transition: border-color .12s, box-shadow .12s;
            letter-spacing: -.01em;
        }

        #pos-root .pos-tender:focus {
            border-color: var(--blue);
            background: var(--s0);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .1);
        }

        #pos-root .pos-change-row {
            min-height: 22px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }

        #pos-root .pos-change-row.credit {
            color: var(--amber);
        }

        #pos-root .pos-change-row.due {
            color: var(--red);
        }

        /* Complete button */
        #pos-root .pos-complete-wrap {
            margin-top: auto !important;
            /* Pushes to bottom */
            display: flex;
            flex-direction: column;
            gap: 12px !important;
        }

        #pos-root .pos-btn {
            padding: 20px !important;
            border-radius: var(--r-lg);
            border: none;
            font-size: 17px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            letter-spacing: .01em;
            transition: all .14s;
        }

        #pos-root .pos-btn.ready {
            background: var(--green);
            color: #fff;
            box-shadow: 0 4px 14px rgba(22, 163, 74, .3);
        }

        #pos-root .pos-btn.ready:hover {
            filter: brightness(1.07);
            transform: translateY(-2px);
        }

        #pos-root .pos-btn.ready:active {
            transform: scale(0.98) translateY(0);
            box-shadow: none;
        }

        #pos-root .pos-btn:disabled {
            background: var(--s3);
            color: var(--t2);
            cursor: not-allowed;
            box-shadow: none;
        }

        #pos-root .pos-hints {
            text-align: center;
            font-size: 11.5px;
            color: var(--t2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #pos-root .pos-hints kbd {
            background: var(--s2);
            border: 1px solid var(--b1);
            border-radius: 5px;
            padding: 3px 8px !important;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--t1);
        }
    </style>

    <div id="pos-root" x-data="posTerminal()" x-init="init()" @keydown.window="handleKey($event)"
        @print-receipt.window="printReceipt($event.detail[0].invoiceId)">
        <div class="pos-wrap">

            {{-- ════════════════════════════════
             TOP — Product Browser
        ════════════════════════════════ --}}
            <div class="pos-top-panel">

                <div class="pos-toolbar">
                    <div class="pos-search-wrap">
                        <input class="pos-search" x-ref="searchInput" wire:model.live.debounce.250ms="search"
                            type="text" placeholder="Search products..." autocomplete="off" />
                    </div>
                    <span class="pos-kbd-tag">/ to search</span>
                </div>

                <div class="pos-cats">
                    @foreach ($this->categories as $cat)
                        <button wire:click="setCategory('{{ $cat['id'] }}')"
                            class="pos-cat {{ $activeCategory === $cat['id'] ? 'active' : '' }}">{{ $cat['name'] }}</button>
                    @endforeach
                </div>

                <div class="pos-grid">
                    @forelse($this->products as $index => $product)
                        @php
                            $stockClass = $product['stock'] > 5 ? 'ok' : ($product['stock'] > 0 ? 'low' : '');
                        @endphp
                        <button wire:key="prod-{{ $product['type'] }}-{{ $product['id'] }}"
                            wire:click="addToCart('{{ addslashes($product['type']) }}', {{ $product['id'] }})"
                            class="pos-card" @if ($product['stock'] <= 0) disabled @endif
                            title="{{ $product['name'] }}">
                            <span class="pos-card-stock {{ $stockClass }}">{{ $product['stock'] }}</span>
                            <span class="pos-card-name">{{ $product['name'] }}</span>
                            <span class="pos-card-price">LKR {{ number_format($product['price'], 0) }}</span>
                            @if ($index < 9)
                                <span class="pos-card-num">[{{ $index + 1 }}]</span>
                            @endif
                        </button>
                    @empty
                        <div class="pos-empty">
                            <x-heroicon-o-archive-box-x-mark style="width:36px;height:36px;opacity:.3;" />
                            <span>No items found</span>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ════════════════════════════════
             BOTTOM — Checkout Dock
        ════════════════════════════════ --}}
            <div class="pos-bottom-panel">

                {{-- ── Column 1: Cart ── --}}
                <div class="pos-cart-col">
                    <div class="pos-cust">
                        <select class="pos-cust-sel" wire:model="customerId">
                            @foreach ($customers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <button class="pos-new-btn" x-on:click="$wire.mountAction('createCustomer')">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            New
                        </button>
                    </div>

                    <div class="pos-cart" x-ref="cartList">
                        @forelse($cart as $key => $item)
                            @php $sk = addslashes($key); @endphp
                            <div wire:key="ci-{{ $sk }}" class="pos-item">
                                <div style="min-width:0;">
                                    <div class="pos-item-name" title="{{ $item['name'] }}">{{ $item['name'] }}</div>
                                    <div class="pos-item-sub">
                                        <input type="number" step="0.01" class="pos-price-edit"
                                            wire:change="updateItemPrice('{{ $sk }}', $event.target.value)"
                                            value="{{ $item['unit_price'] }}" title="Edit unit price" />
                                        <span style="color:var(--t2);">×</span>
                                        <span
                                            style="font-weight:600;color:var(--t0);font-size:13px;">{{ $item['quantity'] }}</span>
                                    </div>
                                </div>
                                <div class="pos-item-ctrl">
                                    <button class="pos-qbtn rm"
                                        wire:click="decreaseQuantity('{{ $sk }}')">−</button>
                                    <span class="pos-qty">{{ $item['quantity'] }}</span>
                                    <button class="pos-qbtn"
                                        wire:click="increaseQuantity('{{ $sk }}')">+</button>
                                    <div class="pos-rtotal">{{ number_format($item['row_total'], 0) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="pos-empty-cart">
                                <x-heroicon-o-shopping-bag
                                    style="width:40px;height:40px;opacity:.25;margin-bottom:8px;" />
                                <span style="font-weight:600; font-size: 15px;">Cart is empty</span>
                                <span style="font-size:12.5px;">Press 1–9 to quick-add</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Column 2: Totals ── --}}
                <div class="pos-totals-col">
                    <div>
                        <div class="pos-trow">
                            <span class="pos-tlbl">Subtotal</span>
                            <span class="pos-tval">LKR {{ number_format($subTotal, 2) }}</span>
                        </div>
                        <div class="pos-disc-wrap">
                            <select class="pos-disc-sel" wire:model.live="discountType">
                                <option value="amount">LKR</option>
                                <option value="percentage">%</option>
                            </select>
                            <input class="pos-disc-input" type="number" step="0.01" min="0"
                                wire:model.live.debounce.350ms="discountValue" placeholder="0.00 — discount" />
                        </div>
                        <div class="pos-disc-badge">
                            @if ($discountAmount > 0)
                                − LKR {{ number_format($discountAmount, 2) }} discount applied
                            @endif
                        </div>
                    </div>

                    <div class="pos-grand-row">
                        <span class="pos-grand-lbl">Grand Total</span>
                        <span class="pos-grand-val">LKR {{ number_format($grandTotal, 2) }}</span>
                    </div>
                </div>

                {{-- ── Column 3: Payment & Complete ── --}}
                <div class="pos-pay-col">
                    <div>
                        <select class="pos-pay-sel" wire:model="accountId">
                            @foreach ($paymentMethods as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="pos-tender-wrap">
                            <span class="pos-tender-pre">LKR</span>
                            <input class="pos-tender" type="number" step="0.01" min="0"
                                wire:model.live="amountPaid" placeholder="0.00" x-ref="tenderInput" />
                        </div>
                        @php
                            $_paid = (float) $amountPaid;
                            $_chClass = '';
                            $_chText = '';
                            if ($_paid > 0 && $_paid < $grandTotal) {
                                $_chClass = 'due';
                                $_chText = 'Balance due: LKR ' . number_format($grandTotal - $_paid, 2);
                            } elseif ($_paid > $grandTotal && $grandTotal > 0) {
                                $_chClass = 'credit';
                                $_chText = 'Change to return: LKR ' . number_format($_paid - $grandTotal, 2);
                            }
                        @endphp
                        <div class="pos-change-row {{ $_chClass }}">{{ $_chText }}</div>
                    </div>

                    @php
                        $_ready = !empty($cart) && $grandTotal > 0;
                        $_btnClass = $_ready ? 'pos-btn ready' : 'pos-btn';
                    @endphp
                    <div class="pos-complete-wrap">

                        <button wire:click="toggleCreditSale" class="pos-btn"
                            style="background: {{ $isCreditSale ? 'var(--amber)' : 'var(--s2)' }}; color: {{ $isCreditSale ? '#fff' : 'var(--t0)' }}; margin-bottom: 5px; padding: 12px;">
                            {{ $isCreditSale ? 'Credit Sale (Unpaid)' : 'Mark as Credit Sale' }}
                        </button>
                        <button wire:click="processSale" wire:loading.attr="disabled"
                            @if (empty($cart) || $grandTotal <= 0) disabled @endif class="{{ $_btnClass }}">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Complete Sale
                        </button>
                        <div class="pos-hints">
                            <kbd>Enter</kbd> complete
                            <kbd>/</kbd> search
                            <kbd>T</kbd> tender
                            <kbd>Esc</kbd> undo
                            <kbd>1–9</kbd> add
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <x-filament-actions::modals />
    </div>

    <script>
        function posTerminal() {
            return {
                init() {
                    this.$nextTick(() => this.$refs.searchInput?.focus());

                    // Auto-collapse the sidebar on load to provide maximum breathing space
                    setTimeout(() => {
                        const sidebar = document.querySelector('aside.fi-sidebar');
                        if (sidebar && !sidebar.classList.contains('fi-sidebar-collapsed')) {
                            const collapseBtn = document.querySelector(
                            'button[x-on\\:click*="isSidebarCollapsed"]');
                            if (collapseBtn) collapseBtn.click();
                        }
                    }, 50);
                },

                handleKey(e) {
                    const tag = document.activeElement?.tagName;
                    const inField = tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA';

                    if (e.key === '/' && !inField) {
                        e.preventDefault();
                        this.$refs.searchInput?.focus();
                        return;
                    }
                    if ((e.key === 't' || e.key === 'T') && !inField) {
                        e.preventDefault();
                        this.$refs.tenderInput?.focus();
                        this.$refs.tenderInput?.select();
                        return;
                    }
                    if (e.key === 'Escape') {
                        if (inField) {
                            document.activeElement.blur();
                        } else {
                            this.$wire.removeLastCartItem();
                        }
                        return;
                    }
                    if (e.key === 'Enter' && !inField) {
                        e.preventDefault();
                        this.$wire.processSale();
                        return;
                    }
                    if (!inField && e.key >= '1' && e.key <= '9') {
                        const cards = document.querySelectorAll('.pos-card:not(:disabled)');
                        const card = cards[parseInt(e.key) - 1];
                        if (card) {
                            card.click();
                            card.style.borderColor = 'var(--green)';
                            card.style.background = 'var(--green-s)';
                            setTimeout(() => {
                                card.style.borderColor = '';
                                card.style.background = '';
                            }, 220);
                        }
                    }
                },

                printReceipt(invoiceId) {
                    if (invoiceId) {
                        window.open('/pos/receipt/' + invoiceId, 'Receipt', 'width=400,height=650,scrollbars=yes');
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>
