<x-filament-panels::page>
    <div class="flex flex-col gap-8">

        <!-- TOP SECTION -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-white/10 p-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- CART -->
                <div class="lg:col-span-2 flex flex-col gap-5">

                    <!-- CUSTOMER -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0">
                            Select Customer
                        </label>

                        <select wire:model="customerId"
                            class="flex-1 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm py-3 px-3 focus:ring-primary-500">
                            @foreach ($customers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- CART LIST -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 overflow-y-auto h-[300px] space-y-4 shadow-inner">

                        @forelse($cart as $key => $item)
                            @php $safeKey = addslashes($key); @endphp

                            <div wire:key="cart-{{ $safeKey }}"
                                class="flex items-center justify-between p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition">

                                <!-- ITEM INFO -->
                                <div class="flex-1 pr-5 min-w-0 space-y-2">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </h4>

                                    <p class="text-xs text-primary-600 dark:text-primary-400 font-semibold">
                                        LKR {{ number_format($item['unit_price'], 2) }}
                                    </p>
                                </div>

                                <!-- ACTIONS -->
                                <div class="flex items-center gap-4 shrink-0">

                                    <!-- QTY CONTROL -->
                                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-xl px-2 py-1.5 border border-gray-200 dark:border-gray-600 gap-1">
                                        
                                        <button wire:click="decreaseQuantity('{{ $safeKey }}')"
                                            class="p-2 rounded-lg hover:bg-white dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300">
                                            <x-heroicon-m-minus class="w-4 h-4" />
                                        </button>

                                        <span class="w-8 text-center text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $item['quantity'] }}
                                        </span>

                                        <button wire:click="increaseQuantity('{{ $safeKey }}')"
                                            class="p-2 rounded-lg hover:bg-white dark:hover:bg-gray-600 transition text-gray-600 dark:text-gray-300">
                                            <x-heroicon-m-plus class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <!-- DELETE -->
                                    <button wire:click.prevent="removeFromCart('{{ $safeKey }}')"
                                        class="p-2.5 text-danger-500 hover:bg-red-100 dark:hover:bg-red-500/10 rounded-xl transition">
                                        <x-heroicon-m-trash class="w-5 h-5" />
                                    </button>

                                </div>
                            </div>

                        @empty
                            <div class="h-full flex flex-col items-center justify-center text-gray-400 py-6">
                                {{-- <x-heroicon-o-shopping-bag class="w-12 h-12 mb-3 opacity-40" /> --}}
                                <span class="text-sm font-medium">Cart is empty</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- TOTAL -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 flex flex-col justify-between gap-6 sticky top-4 h-fit shadow-inner">
                    
                    <div class="space-y-2">
                        <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">
                            Grand Total
                        </span>

                        <div class="text-4xl font-black text-primary-600 dark:text-primary-400 tracking-tight">
                            LKR {{ number_format($grandTotal, 2) }}
                        </div>
                    </div>

                    <button wire:click="processSale"
                        wire:loading.attr="disabled"
                        @if (empty($cart)) disabled @endif
                        class="w-full py-4 rounded-xl text-white font-bold text-lg transition shadow-md flex items-center justify-center
                        {{ empty($cart) 
                            ? 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed' 
                            : 'bg-success-600 hover:bg-success-500 active:scale-95' }}">

                        <x-heroicon-m-check-circle class="w-6 h-6 mr-2" />
                        Complete Sale
                    </button>
                </div>

            </div>
        </div>

        <!-- PRODUCTS -->
        <div class="space-y-6">

            <!-- SEARCH + CATEGORY -->
            <div class="flex flex-col gap-4 bg-white dark:bg-gray-900 p-5 rounded-2xl shadow-sm ring-1 ring-gray-200 dark:ring-white/10">

                <!-- SEARCH -->
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search items..."
                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm py-3 px-4 focus:ring-primary-500">

                <!-- CATEGORY -->
                <div class="flex overflow-x-auto hide-scrollbar gap-3 pb-1">
                    @foreach ($this->categories as $category)
                        <button wire:click="setCategory('{{ $category['id'] }}')"
                            class="shrink-0 whitespace-nowrap px-5 py-2.5 rounded-xl text-sm font-bold border transition
                            {{ $activeCategory === $category['id']
                                ? 'bg-primary-600 border-primary-600 text-white ring-2 ring-primary-500/30'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            {{ $category['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- PRODUCTS GRID -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-5">

                @forelse($this->products as $product)
                    <button wire:key="product-{{ $product['type'] }}-{{ $product['id'] }}"
                        wire:click="addToCart('{{ addslashes($product['type']) }}', {{ $product['id'] }})"
                        @if ($product['stock'] <= 0) disabled @endif
                        class="flex flex-col p-5 min-h-[130px] rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm transition
                        {{ $product['stock'] <= 0 
                            ? 'opacity-50 grayscale cursor-not-allowed' 
                            : 'hover:ring-2 hover:ring-primary-500 hover:shadow-md active:scale-95' }}">

                        <div class="flex justify-between items-start gap-2">
                            <span class="text-sm font-bold line-clamp-2 text-left text-gray-800 dark:text-gray-200">
                                {{ $product['name'] }}
                            </span>

                            <span class="px-2 py-1 text-xs font-black rounded-md
                                {{ $product['stock'] > 0 
                                    ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400'
                                    : 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' }}">
                                {{ $product['stock'] }}
                            </span>
                        </div>

                        <div class="mt-auto pt-4 text-primary-600 dark:text-primary-400 font-black">
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

    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { scrollbar-width: none; }
    </style>
</x-filament-panels::page>
