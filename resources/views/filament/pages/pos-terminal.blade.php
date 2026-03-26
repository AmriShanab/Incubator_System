<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="md:col-span-2">
            {{ $this->form }}
        </div>
        
        <div class="md:col-span-1">
            <x-filament::section class="text-center sticky top-6">
                <h2 class="text-xl font-bold text-gray-500 dark:text-gray-400 mb-2">Amount Due</h2>
                
                <div class="text-4xl font-black text-primary-600 mb-6">
                    LKR {{ number_format($grandTotal, 2) }}
                </div>
                
                <x-filament::button 
                    wire:click="processSale" 
                    size="xl" 
                    color="success" 
                    class="w-full text-lg shadow-lg"
                    icon="heroicon-m-banknotes"
                >
                    Complete Cash Sale
                </x-filament::button>

                <p class="text-xs text-gray-400 mt-4">
                    Items will be deducted from stock and LKR {{ number_format($grandTotal, 2) }} will be deposited into the Cash account.
                </p>
            </x-filament::section>
        </div>

    </div>
</x-filament-panels::page>