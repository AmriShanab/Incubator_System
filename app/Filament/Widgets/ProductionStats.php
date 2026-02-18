<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProductionStats extends BaseWidget
{
    protected static ?int $sort = 1; // Put this at the very top

    protected function getStats(): array
    {
        // 1. Total Income (Delivered Invoices)
        $income = Invoice::where('status', '!=', 'cancelled')->sum('total_amount');

        // 2. Total Expenses (Received Purchase Orders)
        $expense = PurchaseOrder::where('status', 'received')->sum('total_amount');

        // 3. Net Profit (Cash Flow)
        $profit = $income - $expense;

        return [
            Stat::make('Total Revenue', Number::currency($income, 'LKR'))
                ->description('All time sales')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Dummy trend for visual appeal
                ->color('success'),

            Stat::make('Total Expenses', Number::currency($expense, 'LKR'))
                ->description('Material Purchases')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('danger'),

            Stat::make('Net Cash Flow', Number::currency($profit, 'LKR'))
                ->description('Revenue - Expenses')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($profit >= 0 ? 'success' : 'danger'),
        ];
    }
}