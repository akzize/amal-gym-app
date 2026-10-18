<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Models\Discount;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        // No delete: payments keep a copy of what they got, but deactivating keeps the catalog history readable
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label(__('resources.discount.name'))
                    ->description(fn(Discount $record) => $record->name)
                    ->searchable(['name', 'name_ar']),
                TextColumn::make('value')
                    ->label(__('resources.discount.value'))
                    ->formatStateUsing(fn(Discount $record) => $record->type === Discount::TYPE_PERCENT
                        ? (float) $record->value . '%'
                        : number_format($record->value, 2) . ' MAD'),
                TextColumn::make('paymentType.name_ar')
                    ->label(__('resources.discount.payment_type'))
                    ->placeholder(__('resources.discount.all_payment_types')),
                TextColumn::make('conditions')
                    ->label(__('resources.discount.conditions'))
                    ->state(fn(Discount $record) => array_filter([
                        $record->min_months ? __('resources.discount.condition_months', ['months' => $record->min_months]) : null,
                        $record->new_members_only ? __('resources.discount.new_members_only') : null,
                        $record->valid_from || $record->valid_until
                            ? ($record->valid_from?->format('Y-m-d') ?? '…') . ' → ' . ($record->valid_until?->format('Y-m-d') ?? '…')
                            : null,
                        $record->is_automatic ? __('resources.discount.is_automatic') : null,
                    ]))
                    ->badge()
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label(__('resources.discount.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
