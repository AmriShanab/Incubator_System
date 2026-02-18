<x-filament-panels::page>
    <x-filament-panels::form wire:submit="calculate">
        {{ $this->form }}
        
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Analyze Stock
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    @if($analysisResults)
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Results for {{ $analysisResults['quantity'] }}x {{ $analysisResults['product'] }}</h2>
            
            @if($analysisResults['feasible'])
                <div class="p-4 mb-4 bg-green-100 text-green-800 rounded-lg border border-green-200">
                    ✅ <strong>Good to Go!</strong> You have enough materials for this production run.
                </div>
            @else
                <div class="p-4 mb-4 bg-red-100 text-red-800 rounded-lg border border-red-200">
                    ❌ <strong>Insufficient Stock!</strong> You need to purchase materials before starting.
                </div>
            @endif

            <div class="overflow-x-auto border rounded-lg shadow-sm">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Material</th>
                            <th class="px-6 py-3">Current Stock</th>
                            <th class="px-6 py-3">Required</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analysisResults['materials'] as $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $item['material_name'] }}</td>
                                <td class="px-6 py-4">{{ $item['stock'] }}</td>
                                <td class="px-6 py-4">{{ $item['required'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $item['color'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>