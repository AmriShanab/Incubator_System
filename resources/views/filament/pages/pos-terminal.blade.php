<x-filament-panels::page>

    {{-- ================================================================
     POS TERMINAL — Dense Products / Spacious Checkout
     Keyboard: / = search | 1–9 = quick-add | Enter = complete
               Esc = undo last | T = focus tender
     ================================================================ --}}

    <style>
        /* Changed from DM Sans to Poppins to match your Provider */
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap');

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
           DESIGN TOKENS — light / dark (CYAN THEME)
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

            /* Primary Theme: Cyan */
            --primary: #0891b2;
            /* Cyan 600 */
            --primary-s: #ecfeff;
            /* Cyan 50 */
            --primary-d: #a5f3fc;
            /* Cyan 200 */

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
            --sh-up: 0 -4px 24px rgba(0, 0, 0, .06);

            font-family: 'Poppins', sans-serif;
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

            /* Primary Theme: Cyan (Dark Mode) */
            --primary: #06b6d4;
            /* Cyan 500 */
            --primary-s: #083344;
            /* Cyan 950 */
            --primary-d: #155e75;
            /* Cyan 800 */

            --amber: #f59e0b;
            --amber-s: #1a1000;
            --red: #f87171;
            --red-s: #1a0606;
            --blue: #60a5fa;
            --blue-s: #0d1f3c;

            --sh-sm: 0 1px 3px rgba(0, 0, 0, .5);
            --sh-md: 0 4px 16px rgba(0, 0, 0, .6);
            --sh-up: 0 -4px 24px rgba(0, 0, 0, .3);
        }

        #pos-root .pos-wrap {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* ══════════════════════════════════════
           TOP — Product Browser (COMPACT)
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
            padding: 10px 20px !important;
            /* Tighter padding */
            background: var(--s0);
            border-bottom: 1px solid var(--b0);
        }

        #pos-root .pos-qty-edit {
            width: 45px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            background: var(--s0);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-sm);
            color: var(--t0);
            padding: 4px 2px !important;
            outline: none;
        }

        #pos-root .pos-qty-edit:focus {
            border-color: var(--primary);
        }

        #pos-root .pos-uom-badge {
            font-size: 10px;
            background: var(--s2);
            color: var(--t1);
            padding: 2px 4px !important;
            border-radius: 4px;
            font-weight: bold;
        }

        #pos-root .pos-search-wrap {
            position: relative;
            flex: 1;
        }

        #pos-root .pos-search {
            width: 100%;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 10px 16px 10px 40px !important;
            border-radius: var(--r-md);
            font-size: 14px;
            /* Tighter padding */
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        #pos-root .pos-search:focus {
            border-color: var(--primary);
            background: var(--s0);
            box-shadow: 0 0 0 3px rgba(8, 145, 178, .12);
        }

        #pos-root .pos-kbd-tag {
            background: var(--s2);
            border: 1px solid var(--b1);
            border-radius: var(--r-sm);
            padding: 6px 10px !important;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t2);
        }

        #pos-root .pos-cats {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px !important;
            background: var(--s0);
            border-bottom: 1px solid var(--b0);
            overflow-x: auto;
            /* Tighter padding */
        }

        #pos-root .pos-cats::-webkit-scrollbar {
            display: none;
        }

        #pos-root .pos-cat {
            padding: 6px 14px !important;
            border-radius: var(--r-pill);
            border: 1.5px solid var(--b1);
            background: transparent;
            color: var(--t1);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .12s;
        }

        #pos-root .pos-cat:hover {
            background: var(--s2);
            color: var(--t0);
            border-color: var(--b0);
        }

        #pos-root .pos-cat.active {
            background: var(--primary-s);
            border-color: var(--primary-d);
            color: var(--primary);
        }

        #pos-root .pos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px !important;
            padding: 16px 20px !important;
            overflow-y: auto;
            align-content: start;
        }

        #pos-root .pos-grid::-webkit-scrollbar {
            width: 6px;
        }

        #pos-root .pos-grid::-webkit-scrollbar-thumb {
            background: var(--b1);
            border-radius: 6px;
        }

        #pos-root .pos-card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px !important;
            min-height: 85px;
            background: var(--s0);
            border: 1.5px solid var(--b0);
            /* Reduced min-height */
            border-radius: var(--r-md);
            cursor: pointer;
            text-align: left;
            box-shadow: var(--sh-sm);
            transition: border-color .12s, box-shadow .12s, transform .08s;
            width: 100%;
        }

        #pos-root .pos-card:hover:not(:disabled) {
            border-color: var(--primary);
            box-shadow: var(--sh-md);
        }

        #pos-root .pos-card:active:not(:disabled) {
            transform: scale(0.96);
        }

        #pos-root .pos-card:disabled {
            opacity: .4;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        #pos-root .pos-card-stock {
            position: absolute;
            top: 6px;
            right: 6px;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px !important;
            border-radius: 4px;
            background: var(--s2);
            color: var(--t2);
            font-family: 'JetBrains Mono', monospace;
        }

        #pos-root .pos-card-stock.ok {
            background: var(--primary-s);
            color: var(--primary);
        }

        #pos-root .pos-card-stock.low {
            background: var(--amber-s);
            color: var(--amber);
        }

        #pos-root .pos-card-name {
            font-size: 12px;
            font-weight: 600;
            line-height: 1.3;
            color: var(--t0);
            padding-right: 24px !important;
        }

        #pos-root .pos-card-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            margin-top: auto;
        }

        #pos-root .pos-card-num {
            position: absolute;
            bottom: 6px;
            right: 6px;
            font-size: 10px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t2);
            opacity: .6;
        }

        /* ══════════════════════════════════════
           BOTTOM — Checkout Dock (SCROLLABLE & SPACIOUS)
        ══════════════════════════════════════ */
        #pos-root .pos-bottom-panel {
            height: 350px;
            max-height: 45vh;
            /* Force it to shrink to 45% of the screen max, leaving 55% for products */
            flex-shrink: 0;
            display: grid;
            grid-template-columns: minmax(330px, 1.3fr) minmax(260px, 1fr) minmax(260px, 1fr);
            background: var(--s0);
            border-top: 1px solid var(--b0);
            box-shadow: var(--sh-up);
            z-index: 10;
            overflow-x: auto;
            overflow-y: auto;
        }

        #pos-root select.pos-cust-sel,
        #pos-root select.pos-pay-sel,
        #pos-root select.pos-disc-sel {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' viewBox='0 0 24 24'%3E%3Cpath stroke='%239296a3' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
        }

        /* ── Col 1: Cart ── */
        #pos-root .pos-cart-col {
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--b0);
            background: var(--s0);
            overflow-y: auto;
        }

        #pos-root .pos-cust {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px !important;
            border-bottom: 1px solid var(--b0);
            background: var(--s1);
        }

        #pos-root .pos-cust-sel {
            flex: 1;
            background-color: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 8px 36px 8px 12px !important;
            border-radius: var(--r-md);
            font-size: 13px;
            outline: none;
            cursor: pointer;
        }

        #pos-root .pos-new-btn {
            background: var(--s0);
            border: 1.5px solid var(--b1);
            color: var(--t1);
            padding: 8px 12px !important;
            border-radius: var(--r-md);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        #pos-root .pos-cart {
            flex: 1;
            overflow-y: visible;
            padding: 12px 16px !important;
            display: flex;
            flex-direction: column;
            gap: 8px !important;
        }

        #pos-root .pos-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-md);
            padding: 10px 14px !important;
        }

        #pos-root .pos-item-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--t0);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #pos-root .pos-item-sub {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
            font-size: 12px;
            color: var(--t1);
        }

        #pos-root .pos-price-edit {
            background: var(--s0);
            border: 1.5px solid var(--b0);
            border-radius: var(--r-sm);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            width: 80px;
            padding: 4px 6px !important;
            outline: none;
        }

        #pos-root .pos-item-ctrl {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        #pos-root .pos-qbtn {
            width: 28px;
            height: 28px;
            border-radius: var(--r-sm);
            border: 1.5px solid var(--b1);
            background: var(--s0);
            color: var(--t1);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        #pos-root .pos-qbtn.rm {
            color: var(--red);
            border-color: var(--b0);
        }

        #pos-root .pos-qty {
            min-width: 24px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        #pos-root .pos-rtotal {
            min-width: 80px;
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        #pos-root .pos-empty-cart {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--t2);
            font-size: 13.5px;
            min-height: 120px;
        }

        /* ── Col 2: Totals ── */
        #pos-root .pos-totals-col {
            display: flex;
            flex-direction: column;
            padding: 20px !important;
            background: var(--s1);
            border-right: 1px solid var(--b0);
            overflow-y: auto;
        }

        #pos-root .pos-trow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0 !important;
        }

        #pos-root .pos-tlbl {
            font-size: 13.5px;
            color: var(--t1);
        }

        #pos-root .pos-tval {
            font-size: 14px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--t0);
            font-weight: 600;
        }

        #pos-root .pos-disc-wrap {
            display: flex;
            gap: 8px;
            margin: 12px 0 6px !important;
        }

        #pos-root .pos-disc-sel {
            width: 85px;
            flex-shrink: 0;
            background-color: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 8px 28px 8px 12px !important;
            border-radius: var(--r-md);
            font-size: 13px;
        }

        #pos-root .pos-disc-input {
            flex: 1;
            background: var(--s0);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 8px 12px !important;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
        }

        #pos-root .pos-disc-badge {
            min-height: 18px;
            font-size: 11.5px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--amber);
            text-align: right;
            font-weight: 600;
        }

        #pos-root .pos-grand-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-top: auto !important;
            padding-top: 16px !important;
            border-top: 1.5px solid var(--b0);
        }

        #pos-root .pos-grand-lbl {
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--t1);
        }

        #pos-root .pos-grand-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -.01em;
        }

        /* ── Col 3: Payment ── */
        #pos-root .pos-pay-col {
            display: flex;
            flex-direction: column;
            padding: 20px !important;
            background: var(--s0);
            overflow-y: auto;
        }

        #pos-root .pos-pay-sel {
            width: 100%;
            background-color: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 10px 32px 10px 14px !important;
            border-radius: var(--r-md);
            font-size: 13.5px;
            outline: none;
            margin-bottom: 12px !important;
        }

        #pos-root .pos-tender-wrap {
            position: relative;
            margin-bottom: 6px !important;
        }

        #pos-root .pos-tender-pre {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--t2);
            font-family: 'JetBrains Mono', monospace;
        }

        #pos-root .pos-tender {
            width: 100%;
            background: var(--s1);
            border: 1.5px solid var(--b0);
            color: var(--t0);
            padding: 14px 14px 14px 48px !important;
            border-radius: var(--r-md);
            font-size: 20px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
        }

        #pos-root .pos-change-row {
            min-height: 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: 13.5px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }

        #pos-root .pos-change-row.credit {
            color: var(--amber);
        }

        #pos-root .pos-change-row.due {
            color: var(--red);
        }

        /* Complete & Credit Buttons */
        #pos-root .pos-complete-wrap {
            margin-top: auto !important;
            display: flex;
            flex-direction: column;
            gap: 10px !important;
            padding-top: 16px;
        }

        #pos-root .pos-btn-row {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        #pos-root .pos-btn {
            padding: 14px !important;
            border-radius: var(--r-lg);
            border: none;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .14s;
            flex: 1;
        }

        #pos-root .pos-btn.credit-btn {
            flex: 0.8;
            font-size: 13.5px;
        }

        #pos-root .pos-btn.ready {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 14px rgba(8, 145, 178, .3);
            flex: 1.2;
        }

        #pos-root .pos-btn:disabled {
            background: var(--s3) !important;
            color: var(--t2) !important;
            cursor: not-allowed;
            box-shadow: none;
            border: none;
        }

        #pos-root .pos-hints {
            text-align: center;
            font-size: 11px;
            color: var(--t2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        #pos-root .pos-hints kbd {
            background: var(--s2);
            border: 1px solid var(--b1);
            border-radius: 5px;
            padding: 2px 6px !important;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
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
                    <span class="pos-kbd-tag">/ search</span>
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
                        <div class="pos-empty"
                            style="grid-column: 1/-1; text-align: center; margin-top: 40px; color: var(--t2);">
                            <x-heroicon-o-archive-box-x-mark style="width:36px;height:36px;opacity:.3;margin:0 auto;" />
                            <div style="margin-top: 8px; font-size: 14px;">No items found</div>
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
                                    <div class="pos-item-name" title="{{ $item['name'] }}">
                                        {{ $item['name'] }}
                                        <span class="pos-uom-badge">{{ $item['uom'] ?? 'pcs' }}</span>
                                    </div>
                                    <div class="pos-item-sub">
                                        <input type="number" step="0.01" class="pos-price-edit"
                                            wire:change="updateItemPrice('{{ $sk }}', $event.target.value)"
                                            value="{{ $item['unit_price'] }}" title="Edit unit price" />
                                        <span style="color:var(--t2);">×</span>
                                        <span
                                            style="font-weight:700;color:var(--t0);font-size:13px;">{{ $item['quantity'] }}</span>
                                    </div>
                                </div>
                                <div class="pos-item-ctrl">
                                    <button class="pos-qbtn rm"
                                        wire:click="decreaseQuantity('{{ $sk }}')">−</button>
                                    <input type="number" step="any" class="pos-qty-edit"
                                        wire:change="updateQuantity('{{ $sk }}', $event.target.value)"
                                        value="{{ $item['quantity'] }}" />
                                    <button class="pos-qbtn"
                                        wire:click="increaseQuantity('{{ $sk }}')">+</button>
                                    <div class="pos-rtotal">{{ number_format($item['row_total'], 0) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="pos-empty-cart">
                                <x-heroicon-o-shopping-bag
                                    style="width:36px;height:36px;opacity:.25;margin-bottom:8px;" />
                                <span style="font-weight:600;">Cart is empty</span>
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

                        <div class="pos-btn-row">
                            <button wire:click="toggleCreditSale" class="pos-btn credit-btn"
                                style="background: {{ $isCreditSale ? 'var(--amber)' : 'var(--s2)' }}; color: {{ $isCreditSale ? '#fff' : 'var(--t0)' }}; border: 1.5px solid {{ $isCreditSale ? 'var(--amber)' : 'var(--b0)' }};">
                                {{ $isCreditSale ? 'Unpaid Credit' : 'Mark Credit' }}
                            </button>

                            <button wire:click="processSale" wire:loading.attr="disabled"
                                @if (empty($cart) || $grandTotal <= 0) disabled @endif class="{{ $_btnClass }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Complete Sale
                            </button>
                        </div>

                        <div class="pos-hints">
                            <kbd>Enter</kbd> complete &nbsp;
                            <kbd>T</kbd> tender &nbsp;
                            <kbd>Esc</kbd> undo &nbsp;
                            <kbd>1-9</kbd> add
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
                        if (inField) document.activeElement.blur();
                        else this.$wire.removeLastCartItem();
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
                            card.style.borderColor = 'var(--primary)';
                            card.style.background = 'var(--primary-s)';
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
