<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Analytics';

    // protected ?string $description = __('resources.dashboard.financial_overview_description');
    protected static ?int $sort = 1;

    protected function getHeading(): ?string
    {
        return __('resources.dashboard.payments_statistics_title');
    }
    protected function getDescription(): ?string
    {
        return __('resources.dashboard.financial_overview_description');
    }
    protected function getStats(): array
    {
        // Calculate Total Income This Month
        $monthlyIncome = Payment::where('status', Payment::STATUS_PAID)->whereBetween('payment_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount_paid');
        $unpaidBalance = Payment::where('status', Payment::STATUS_UNPAID)->sum(DB::raw('amount_due - amount_paid'));
        return [
            Stat::make(__('resources.dashboard.widget_title_total_income'), $monthlyIncome . ' MAD')
                // ->description('32k increase')
                ->icon('heroicon-m-arrow-trending-up')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make(__('resources.dashboard.widget_title_unpaid_balance'), $unpaidBalance . ' MAD')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('warning'),
        ];
    }
}
