<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AccountOverview extends BaseWidget
{
    // This allows the widget to know WHICH account we are looking at
    public ?Model $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Physical Balance', 'LKR ' . number_format($this->record->balance, 2))
                ->description('Total money currently in this account')
                ->color('primary'),
                
            Stat::make('Investment (Capital Pool)', 'LKR ' . number_format($this->record->capital_pool, 2))
                ->description('Reserved strictly for materials')
                ->color('warning'),
                
            Stat::make('Free Profit', 'LKR ' . number_format($this->record->profit_pool, 2))
                ->description('Available earnings to withdraw')
                ->color('success'),
        ];
    }
}