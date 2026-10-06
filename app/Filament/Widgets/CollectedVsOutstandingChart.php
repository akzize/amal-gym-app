<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CollectedVsOutstandingChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return __('resources.dashboard.chart_collected_vs_outstanding');
    }

    public function getDescription(): string
    {
        return __('resources.dashboard.chart_collected_vs_outstanding_description');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $metrics = DashboardMetrics::fromFilters($this->pageFilters);

        return [
            'datasets' => [
                [
                    'label' => __('resources.dashboard.collected'),
                    'data' => array_values($metrics->collectedByMonth()),
                    'backgroundColor' => '#16a34a',
                    'borderRadius' => 4,
                ],
                [
                    'label' => __('resources.dashboard.outstanding'),
                    'data' => array_values($metrics->outstandingByMonth()),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map(fn(CarbonImmutable $month): string => $month->translatedFormat('M Y'), $metrics->months()),
        ];
    }
}
