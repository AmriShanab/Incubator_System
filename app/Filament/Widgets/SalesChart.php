<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Growth (Last 6 Months)';
    
    protected static ?int $sort = 2; // Position it below the stats
    
    protected int | string | array $columnSpan = 'full'; // Stretch full width

    protected function getData(): array
    {
        // Calculate sales per month
        $data = Trend::model(Invoice::class)
            ->between(
                start: now()->subMonths(6),
                end: now(),
            )
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (LKR)',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10B981', // Emerald Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)', // Light green fill
                    'fill' => true,
                    'tension' => 0.4, // Smooth curves
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}