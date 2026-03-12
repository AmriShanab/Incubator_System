<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CashFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Cash Flow';
    
    // Sort order on the dashboard (higher number pushes it down)
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect(range(1, 12))->map(function ($month) {
            return Carbon::create(null, $month, 1)->format('M');
        })->toArray();

        $incomeData = [];
        $expenseData = [];

        // Loop through the months of the current year
        for ($month = 1; $month <= 12; $month++) {
            $incomeData[] = Transaction::where('type', 'in')
                ->whereYear('transaction_date', date('Y'))
                ->whereMonth('transaction_date', $month)
                ->sum('amount');

            $expenseData[] = Transaction::where('type', 'out')
                ->whereYear('transaction_date', date('Y'))
                ->whereMonth('transaction_date', $month)
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (Revenue)',
                    'data' => $incomeData,
                    'backgroundColor' => '#10b981', // Emerald green
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Expenses (Outflow)',
                    'data' => $expenseData,
                    'backgroundColor' => '#ef4444', // Red
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // You can change this to 'bar' if you prefer bar charts!
    }
}