<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user() && in_array(Auth::user()->role, ['admin']);
    }
    protected function getStats(): array
    {
        
        $revenue = Transaction::where('type', 'in')->sum('amount');
        $expenses = Transaction::where('type', 'out')->sum('amount');

        $netProfit = $revenue - $expenses;

        return [
            Stat::make('Total Revenue', 'LKR ' . number_format($revenue, 2))
                ->description('All incoming cash')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]), 

            Stat::make('Total Expenses', 'LKR ' . number_format($expenses, 2))
                ->description('Purchases, Fees, & Bills')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([3, 12, 4, 10, 5, 14, 2]),

            Stat::make('Net Profit', 'LKR ' . number_format($netProfit, 2))
                ->description('Overall SN Tech Profit')
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($netProfit >= 0 ? 'success' : 'danger'),
        ];
    }
}
