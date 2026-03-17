<?php

namespace App\Filament\Resources\AccessoryResource\Pages;

use App\Filament\Resources\AccessoryResource;
use App\Models\Accessory;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAccessories extends ListRecords
{
    protected static string $resource = AccessoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // 1. Always show the "All" tab first
        $tabs = [
            'all' => Tab::make('All Supplies'),
        ];

        // 2. Fetch all unique categories from the database automatically
        $categories = Accessory::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        // 3. Loop through the database categories and generate a tab for each
        foreach ($categories as $category) {
            $tabs[$category] = Tab::make(ucfirst($category))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', $category));
        }

        return $tabs;
    }
}