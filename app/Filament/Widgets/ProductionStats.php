<?php

namespace App\Filament\Widgets;

use App\Models\Incubator;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProductionStats extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $incubators = Incubator::all();
        $stats = [];

        foreach($incubators as $incubator){
            $stock = $incubator->current_stock ?? 0;
            
            $stats[] = Stat::make($incubator->name . '(In Stock)', $stock)
            ->description($stock <= 5 ? 'Low Stock Warning' : 'Stock Level Good')
            ->descriptionIcon($stock <= 5 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
            ->color($stock <= 5 ? 'danger' : 'success');        
        }

        return $stats;
    }

    
}