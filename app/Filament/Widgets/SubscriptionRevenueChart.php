<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

/**
 * Daily subscription revenue for the last seven days, matching the bar chart
 * on the prototype's admin overview.
 */
class SubscriptionRevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * Keeps the verification queue visible below the chart without scrolling.
     */
    protected ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return __('admin.revenue_chart');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $ago) => now()->subDays($ago)->startOfDay());

        $totals = $days->map(fn ($day): float => (float) Invoice::query()
            ->whereBetween('issued_at', [$day, $day->copy()->endOfDay()])
            ->sum('total_egp'));

        return [
            'datasets' => [[
                'label' => __('admin.monthly_revenue'),
                'data' => $totals->all(),
                'backgroundColor' => '#0B3D5C',
                'hoverBackgroundColor' => '#1565A0',
                'borderRadius' => 4,
                'borderSkipped' => 'bottom',
                'maxBarThickness' => 36,
            ]],
            'labels' => $days->map(fn ($day): string => $day->translatedFormat('D'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        $ticks = ['color' => '#9CA3AF', 'font' => ['size' => 11]];

        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'border' => ['color' => '#E5E7EB'], 'ticks' => $ticks],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => '#F3F4F6'],
                    'border' => ['display' => false],
                    'ticks' => $ticks,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
