<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PaymentsByTypeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    // One fixed color per type, in order of appearance
    private const COLORS = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d'];

    public function getHeading(): string
    {
        return __('resources.dashboard.chart_payments_by_type');
    }

    public function getDescription(): string
    {
        return __('resources.dashboard.chart_payments_by_type_description');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $metrics = DashboardMetrics::fromFilters($this->pageFilters);

        $datasets = [];
        foreach (array_values(array_keys($byType = $metrics->collectedByTypeAndMonth())) as $index => $type) {
            $datasets[] = [
                'label' => $type,
                'data' => array_values($byType[$type]),
                'backgroundColor' => self::COLORS[$index % count(self::COLORS)],
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => array_map(fn(CarbonImmutable $month): string => $month->translatedFormat('M Y'), $metrics->months()),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true],
            ],
        ];
    }
}
