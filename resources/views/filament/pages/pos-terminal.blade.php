<x-filament-panels::page>
    <div x-data="{ showCart: false }" class="flex flex-col lg:flex-row gap-6 items-start relative pb-28 lg:pb-0">

        <div class="flex-1 w-full space-y-4">
            
            <div class="bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-1.5 shadow-sm sticky top-4 z-10">
                <div class="relative flex items-center">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 text-gray-400" />
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search products & supplies..." 
                        class="w-full border-none bg-transparent focus:ring-0 text-gray-900 dark:text-white pl-10 py-2.5 text-base md:text-lg placeholder-gray-400"
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-5">
                @forelse($this->products as $product)
                    <button 
                        wire:key="product-{{ $product['type'] }}-{{ $product['id'] }}"
                        wire:click="addToCart('{{ addslashes($product['type']) }}', {{ $product['id'] }})"
                        @if($product['stock'] <= 0) disabled @endif
                        class="relative flex flex-col justify-between p-3 md:p-4 min-h-[140px] md:min-h-[160px] rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm transition-all duration-200 hover:ring-primary-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $product['stock'] <= 0 ? 'opacity-50 grayscale cursor-not-allowed' : 'active:scale-95 cursor-pointer' }}"
                    >
                        <div class="absolute top-2.5 right-2.5">
                            <span class="px-2 py-0.5 md:py-1 text-[9px] md:text-[10px] uppercase tracking-wider font-bold rounded-md {{ $product['stock'] > 0 ? 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400 ring-1 ring-success-600/20' : 'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400 ring-1 ring-danger-600/20' }}">
                                {{ $product['stock'] > 0 ? $product['stock'] . ' In Stock' : 'Out' }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center w-full mt-5">
                            <div class="w-8 h-8 md:w-10 md:h-10 mb-2 md:mb-3 text-{{ $product['color'] }}-500">
                                @if($product['icon'] === 'heroicon-o-cube')
                                    <x-heroicon-o-cube />
                                @else
                                    <x-heroicon-o-tag />
                                @endif
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-900 dark:text-white text-center line-clamp-2 h-8 md:h-10">{{ $product['name'] }}</span>
                        </div>

                        <div class="mt-2 text-center w-full">
                            <span class="text-sm md:text-base font-black text-primary-600 dark:text-primary-400">LKR {{ number_format($product['price'], 2) }}</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-gray-400">
                        <x-heroicon-o-archive-box-x-mark class="w-12 h-12 md:w-16 md:h-16 mb-4 opacity-50" />
                        <p class="text-base md:text-lg font-medium">No products found.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div 
            x-show="showCart" 
            style="display: none;" 
            @click="showCart = false" 
            class="lg:hidden fixed inset-0 z-40 bg-gray-950/50 backdrop-blur-sm transition-opacity"
        ></div>

        <div 
            :class="showCart ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
            class="
                fixed inset-x-0 bottom-0 z-50 h-[85vh] transition-transform duration-300 ease-in-out flex flex-col
                lg:sticky lg:top-4 lg:inset-auto lg:h-[calc(100vh-8rem)] lg:w-[350px] xl:w-[400px] lg:z-10
            "
        >
            <div class="flex-1 flex flex-col bg-white dark:bg-gray-900 rounded-t-3xl lg:rounded-xl shadow-2xl lg:shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                
                <div class="lg:hidden p-4 border-b border-gray-100 dark:border-white/10 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white">Current Sale</h3>
                    <button @click="showCart = false" class="p-2 bg-gray-200 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300 hover:bg-gray-300 transition">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 border-b border-gray-100 dark:border-white/10">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 tracking-wide uppercase">Customer</label>
                    <select wire:model="customerId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500 py-2.5">
                        @foreach($customers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-[150px]">
                    @forelse($cart as $key => $item)
                        <div wire:key="cart-{{ $key }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-white/5 shadow-sm">
                            
                            <div class="flex-1 pr-3 overflow-hidden">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $item['name'] }}</h4>
                                <p class="text-xs text-primary-600 dark:text-primary-400 font-bold mt-1">LKR {{ number_format($item['unit_price'], 2) }}</p>
                            </div>

                            <div class="flex items-center space-x-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-1 shrink-0 shadow-sm">
                                <button wire:click="decreaseQuantity('{{ $key }}')" class="p-1 rounded text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white transition">
                                    <x-heroicon-m-minus class="w-4 h-4" />
                                </button>
                                <span class="w-6 text-center text-sm font-bold text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                <button wire:click="increaseQuantity('{{ $key }}')" class="p-1 rounded text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white transition">
                                    <x-heroicon-m-plus class="w-4 h-4" />
                                </button>
                            </div>

                            <button wire:click="removeFromCart('{{ $key }}')" class="ml-2 p-2 text-danger-500 hover:bg-danger-50 dark:hover:bg-danger-500/10 rounded-lg transition shrink-0">
                                <x-heroicon-m-trash class="w-5 h-5" />
                            </button>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 pt-8 opacity-60">
                            <x-heroicon-o-shopping-bag class="w-16 h-16 mb-4" />
                            <p class="text-sm font-medium">Your cart is empty</p>
                        </div>
                    @endforelse
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-white/10 bg-white dark:bg-gray-900 pb-safe">
                    <div class="flex justify-between items-end mb-5">
                        <span class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Total Due</span>
                        <span class="text-3xl font-black text-primary-600 dark:text-primary-400 leading-none">
                            <span class="text-lg text-primary-500 mr-1">LKR</span>{{ number_format($grandTotal, 2) }}
                        </span>
                    </div>
                    
                    <button 
                        wire:click="processSale" 
                        @click="showCart = false" 
                        @if(empty($cart)) disabled @endif
                        class="w-full flex items-center justify-center py-4 rounded-xl text-white font-bold text-lg transition shadow-md {{ empty($cart) ? 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed' : 'bg-success-600 hover:bg-success-500 hover:shadow-lg active:scale-[0.98]' }}"
                    >
                        <x-heroicon-m-check-circle class="w-6 h-6 mr-2" />
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-white/10 p-4 pb-safe shadow-[0_-10px_20px_rgba(0,0,0,0.1)] flex justify-between items-center">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total Due</p>
                <p class="text-xl font-black text-primary-600 dark:text-primary-400">LKR {{ number_format($grandTotal, 2) }}</p>
            </div>
            <button @click="showCart = true" class="bg-primary-600 hover:bg-primary-500 text-white px-6 py-3 rounded-xl font-bold flex items-center shadow-lg active:scale-95 transition">
                <x-heroicon-o-shopping-cart class="w-5 h-5 mr-2" />
                View Cart ({{ count($cart) }})
            </button>
        </div>

    </div>

    <style>
        .pb-safe { padding-bottom: env(safe-area-inset-bottom, 1.25rem); }
    </style>
</x-filament-panels::page>