<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        
        <a href="{{ url('/admin/pos-terminal') }}" 
           style="background: linear-gradient(135deg, #10b981, #0f766e);"
           class="relative overflow-hidden transition-all duration-300 shadow-md rounded-2xl p-6 hover:-translate-y-1 hover:shadow-xl group">
            <div class="relative flex flex-col items-start justify-between h-full text-white">
                <div class="p-3 mb-6 border shadow-inner bg-white/20 rounded-xl border-white/20">
                    <x-heroicon-o-computer-desktop class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h3 class="text-2xl font-bold tracking-tight">POS Terminal</h3>
                    <p class="mt-1 text-sm font-medium opacity-90">Open Cash Register</p>
                </div>
            </div>
        </a>

        <a href="{{ \App\Filament\Resources\ProductionLogResource::getUrl('create') }}" 
           style="background: linear-gradient(135deg, #3b82f6, #4338ca);"
           class="relative overflow-hidden transition-all duration-300 shadow-md rounded-2xl p-6 hover:-translate-y-1 hover:shadow-xl group">
            <div class="relative flex flex-col items-start justify-between h-full text-white">
                <div class="p-3 mb-6 border shadow-inner bg-white/20 rounded-xl border-white/20">
                    <x-heroicon-o-wrench-screwdriver class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h3 class="text-2xl font-bold tracking-tight">Factory Log</h3>
                    <p class="mt-1 text-sm font-medium opacity-90">Start Manufacturing</p>
                </div>
            </div>
        </a>

        <a href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('create') }}" 
           style="background: linear-gradient(135deg, #f59e0b, #c2410c);"
           class="relative overflow-hidden transition-all duration-300 shadow-md rounded-2xl p-6 hover:-translate-y-1 hover:shadow-xl group">
            <div class="relative flex flex-col items-start justify-between h-full text-white">
                <div class="p-3 mb-6 border shadow-inner bg-white/20 rounded-xl border-white/20">
                    <x-heroicon-o-shopping-cart class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h3 class="text-2xl font-bold tracking-tight">Buy Materials</h3>
                    <p class="mt-1 text-sm font-medium opacity-90">Create Purchase Order</p>
                </div>
            </div>
        </a>

        <a href="{{ \App\Filament\Resources\AccountResource::getUrl('index') }}" 
           style="background: linear-gradient(135deg, #8b5cf6, #be185d);"
           class="relative overflow-hidden transition-all duration-300 shadow-md rounded-2xl p-6 hover:-translate-y-1 hover:shadow-xl group">
            <div class="relative flex flex-col items-start justify-between h-full text-white">
                <div class="p-3 mb-6 border shadow-inner bg-white/20 rounded-xl border-white/20">
                    <x-heroicon-o-truck class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h3 class="text-2xl font-bold tracking-tight">Settle COD</h3>
                    <p class="mt-1 text-sm font-medium opacity-90">Collect Courier Funds</p>
                </div>
            </div>
        </a>

    </div>
</x-filament-widgets::widget>