<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Trainees\TraineeResource;
use App\Support\DashboardMetrics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('resources.dashboard.payments_statistics_title');
    }

    protected function getDescription(): ?string
    {
        $metrics = DashboardMetrics::fromFilters($this->pageFilters);

        return __('resources.dashboard.period', [
            'from' => $metrics->from->toDateString(),
            'to' => $metrics->to->toDateString(),
        ]);
    }

    protected function getColumns(): int | array
    {
        return ['md' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $metrics = DashboardMetrics::fromFilters($this->pageFilters);
        $collected = $metrics->collected();
        $previous = $metrics->previous()->collected();
        $rate = $metrics->collectionRate();
        $unpaid = DashboardMetrics::traineesWithUnpaidMonthlyFee();

        return [
            // ---- Money ----
            Stat::make(__('resources.dashboard.collected'), DashboardMetrics::money($collected))
                ->description($this->changeDescription($collected, $previous))
                ->descriptionIcon($collected >= $previous ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart(array_values($metrics->collectedByMonth()))
                ->color('success'),
            Stat::make(__('resources.dashboard.outstanding'), DashboardMetrics::money($metrics->outstanding()))
                ->description(__('resources.dashboard.outstanding_description'))
                ->chart(array_values($metrics->outstandingByMonth()))
                ->color('warning'),
            Stat::make(__('resources.dashboard.collection_rate'), $rate === null ? '—' : number_format($rate, 1) . '%')
                ->description(__('resources.dashboard.collection_rate_description'))
                ->color(match (true) {
                    $rate === null => 'gray',
                    $rate >= 90 => 'success',
                    $rate >= 70 => 'warning',
                    default => 'danger',
                }),
            Stat::make(__('resources.dashboard.net'), DashboardMetrics::money($metrics->net()))
                ->description(__('resources.dashboard.net_description', ['payouts' => DashboardMetrics::money($metrics->trainerPayoutsPaid())]))
                ->color($metrics->net() >= 0 ? 'success' : 'danger'),

            // ---- Members ----
            Stat::make(__('resources.dashboard.active_trainees'), (string) $metrics->activeTrainees())
                ->description(__('resources.dashboard.new_trainees', ['count' => $metrics->newTrainees()]))
                ->icon('heroicon-o-users'),
            Stat::make(__('resources.dashboard.unpaid_this_month'), (string) $unpaid)
                ->description(__('resources.dashboard.unpaid_this_month_description'))
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->color($unpaid > 0 ? 'danger' : 'success')
                // Opens the trainees list with the very same filter
                ->url(TraineeResource::getUrl('index', ['filters' => ['unpaid_this_month' => ['isActive' => true]]])),
            Stat::make(__('resources.dashboard.expiring_subscriptions'), (string) DashboardMetrics::expiringSubscriptions())
                ->description(__('resources.dashboard.expiring_subscriptions_description'))
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }

    private function changeDescription(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return __('resources.dashboard.vs_previous_none');
        }

        $change = round(100 * ($current - $previous) / $previous, 1);

        return __('resources.dashboard.vs_previous', ['change' => ($change > 0 ? '+' : '') . number_format($change, 1) . '%']);
    }
}
