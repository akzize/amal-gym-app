<?php

namespace App\Filament\Resources\Trainers\Tables;

use App\Filament\Actions\Lifecycle;
use App\Models\Trainer;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Arabic name, latin (French) name as fallback
                TextColumn::make('display_name')
                    ->label(__('resources.trainer.name'))
                    ->searchable(['name', 'name_ar'])
                    ->sortable(['name_ar', 'name']),
                TextColumn::make('salary_type')
                    ->label(__('resources.trainer.salary_type'))
                    ->formatStateUsing(fn(string $state): string => __("resources.trainer.{$state}"))
                    ->badge(),
                TextColumn::make('salary_amount')
                    ->label(__('resources.trainer.salary_amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('resources.trainer.status.name'))
                    ->formatStateUsing(fn(string $state): string => __("resources.trainer.status.{$state}"))
                    ->color(fn(string $state): string => $state === Trainer::STATUS_ACTIVE ? 'success' : 'gray')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Deactivated trainers are hidden by default
                SelectFilter::make('status')
                    ->label(__('resources.trainer.status.name'))
                    ->options([
                        Trainer::STATUS_ACTIVE => __('resources.trainer.status.active'),
                        Trainer::STATUS_INACTIVE => __('resources.trainer.status.inactive'),
                    ])
                    ->default(Trainer::STATUS_ACTIVE),
            ])
            ->recordActions([
                EditAction::make(),
                Lifecycle::deactivateTrainer(),
                Lifecycle::activateTrainer(),
                Lifecycle::deleteIfUnused(),
            ]);
    }
}
