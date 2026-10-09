<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\PaymentInstallment;
use App\Support\DashboardMetrics;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The money received in the dashboard period, one row per installment. These are exactly the
 * rows behind the "collected" card and charts, so the table total always matches them.
 */
class CollectedPaymentsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => DashboardMetrics::fromFilters($this->pageFilters)
                ->collectedQuery()
                ->with(['payment.trainee', 'payment.group', 'payment.paymentType']))
            ->heading(__('resources.dashboard.collected_payments'))
            ->modelLabel(__('resources.payment.modelLabel'))
            ->pluralModelLabel(__('resources.payment.pluralModelLabel'))
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('paid_at')
                    ->label(__('resources.payment.paid_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('payment.trainee.full_arabic_name')
                    ->label(__('resources.trainee.label'))
                    ->state(fn(PaymentInstallment $record): string => $record->payment?->trainee?->full_arabic_name
                        ?: ($record->payment?->trainee?->full_name ?? '—')),
                TextColumn::make('payment.group.name')
                    ->label(__('resources.group.label'))
                    ->placeholder('—'),
                TextColumn::make('payment.paymentType.name_ar')
                    ->label(__('resources.payment.types.label'))
                    ->badge(),
                TextColumn::make('amount_paid')
                    ->label(__('resources.payment.amount_paid'))
                    ->formatStateUsing(fn($state): string => DashboardMetrics::money((float) $state))
                    ->sortable()
                    ->summarize(Sum::make()
                        ->label(__('resources.dashboard.total'))
                        ->formatStateUsing(fn($state): string => DashboardMetrics::money((float) $state))),
            ])
            ->recordUrl(fn(PaymentInstallment $record): ?string => $record->payment
                ? PaymentResource::getUrl('edit', ['record' => $record->payment])
                : null);
    }
}
