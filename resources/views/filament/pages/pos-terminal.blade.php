<x-filament-panels::page>
    <div class="flex flex-col gap-6">

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-white/10 p-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="lg:col-span-2 flex flex-col gap-4">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 w-full">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0">Customer:</label>
                        <select wire:model="customerId" class="flex-1 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm py-2.5 focus:ring-primary-500 min-w-0">
                            @foreach($customers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        
                        <div class="shrink-0">
                            {{ $this->createCustomerAction }}
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-3 overflow-y-auto min-h-[200px] max-h-[280px] space-y-3 shadow-inner">
                        @forelse($cart as $key => $item)
                            @php $safeKey = addslashes($key); @endphp
                            
                            <div wire:key="cart-{{ $safeKey }}" class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-sm">
                                
                                <div class="flex-1 pr-3 min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </h4>
                                    <p class="text-xs text-primary-600 dark:text-primary-400 font-bold mt-1">
                                        LKR {{ number_format($item['unit_price'], 2) }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1 border border-gray-200 dark:border-gray-600">
                                        <button wire:click="decreaseQuantity('{{ $safeKey }}')" class="p-1.5 rounded-md hover:bg-white dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300">
                                            <x-heroicon-m-minus class="w-4 h-4" />
                                        </button>

                                        <span class="w-8 text-center text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $item['quantity'] }}
                                        </span>

                                        <button wire:click="increaseQuantity('{{ $safeKey }}')" class="p-1.5 rounded-md hover:bg-white dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300">
                                            <x-heroicon-m-plus class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <button wire:click.prevent="removeFromCart('{{ $safeKey }}')" class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                        <x-heroicon-m-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="h-full flex flex-col items-center justify-center text-gray-400 py-8">
                                <x-heroicon-o-shopping-bag class="w-10 h-10 mb-2 opacity-50" />
                                <span class="text-sm font-medium">Cart is empty</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 flex flex-col justify-center gap-6 h-full shadow-inner">
                    <div>
                        <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Grand Total</span>
                        <div class="text-3xl sm:text-4xl font-black text-primary-600 dark:text-primary-400 mt-2 truncate">
                            LKR {{ number_format($grandTotal, 2) }}
                        </div>
                    </div>

                    <button wire:click="processSale" wire:loading.attr="disabled"
                        @if (empty($cart)) disabled @endif
                        class="w-full py-4 rounded-xl text-white font-bold text-lg transition shadow-sm flex items-center justify-center
                        {{ empty($cart) ? 'bg-gray-400 dark:bg-gray-700 cursor-not-allowed' : 'active:scale-95' }}"
                        @if (!empty($cart)) style="background-color: #16a34a;" @endif>
                        <x-heroicon-m-check-circle class="w-6 h-6 mr-2" />
                        Complete Sale
                    </button>
                </div>

            </div>
        </div>

        <div class="space-y-6">

            <div class="flex flex-col gap-4 bg-white dark:bg-gray-900 p-4 rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-white/10 w-full">

                <div class="relative w-full">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search items..."
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm py-3 pl-10 pr-3 focus:ring-primary-500 dark:text-white placeholder-gray-400">
                </div>

                <div class="w-full min-w-0">
                    <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1">
                        @foreach ($this->categories as $category)
                            <button wire:click="setCategory('{{ $category['id'] }}')"
                                class="shrink-0 whitespace-nowrap px-5 py-2.5 rounded-xl text-sm font-bold border transition shadow-sm
                                {{ $activeCategory === $category['id']
                                    ? 'bg-primary-600 border-primary-600 text-white ring-2 ring-primary-500/30'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                {{ $category['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-4">
                @forelse($this->products as $product)
                    <button wire:key="product-{{ $product['type'] }}-{{ $product['id'] }}"
                        wire:click="addToCart('{{ addslashes($product['type']) }}', {{ $product['id'] }})"
                        @if ($product['stock'] <= 0) disabled @endif
                        class="flex flex-col p-4 min-h-[120px] rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm transition
                        {{ $product['stock'] <= 0 ? 'opacity-50 grayscale cursor-not-allowed' : 'hover:ring-2 hover:ring-primary-500 hover:shadow-md active:scale-95' }}">

                        <div class="flex justify-between items-start w-full gap-2">
                            <span class="text-sm font-bold line-clamp-2 text-left leading-tight text-gray-800 dark:text-gray-200">
                                {{ $product['name'] }}
                            </span>

                            <span class="shrink-0 px-2 py-0.5 text-xs font-black rounded-md {{ $product['stock'] > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $product['stock'] }}
                            </span>
                        </div>

                        <div class="mt-auto pt-4 text-left w-full text-primary-600 dark:text-primary-400 font-black">
                            LKR {{ number_format($product['price'], 0) }}
                        </div>
                    </button>
                @empty
                    <div class="col-span-full text-center py-10 text-gray-400">
                        <x-heroicon-o-archive-box-x-mark class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <span class="text-lg font-medium">No items found</span>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <x-filament-actions::modals />

    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div x-data="{}"
        @print-receipt.window="
        let invoiceId = $event.detail[0].invoiceId;
        if(invoiceId) {
            window.open('/pos/receipt/' + invoiceId, 'ReceiptWindow', 'width=400,height=600,scrollbars=yes');
        }
    ">
    </div>

</x-filament-panels::page>