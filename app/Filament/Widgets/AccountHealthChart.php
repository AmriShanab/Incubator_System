<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AccountHealthChart extends ChartWidget
{
    protected static ?string $heading = 'The Account Health';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'sm' => 'full', 
        'md' => 1,      
    ];

    protected function getData(): array
    {
        $liquidAccounts = Account::whereIn('type', ['cash', 'bank'])->get();

        $totalCapital = $liquidAccounts->sum('capital_pool');
        $totalProfit = $liquidAccounts->sum('profit_pool');
        return [
            'datasets' => [
                [
                    'label' => 'LKR',
                    'data' => [
                        round($totalCapital, 2),
                        round($totalProfit, 2)
                    ],
                    'backgroundColor' => [
                        '#f59e0b',
                        '#10b981'
                    ],
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => ['Protected Capital', 'Free Profit'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '70%',
        ];
    }
}
