<?php

namespace App\Filament\Resources\Groups\Tables;

use App\Filament\Actions\Lifecycle;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label(__('resources.group.name'))
                ->searchable(),
                TextColumn::make('sport.name')
                ->label(__('resources.sport.label'))
                ->sortable(),
                TextColumn::make('trainer.name')
                ->label(__('resources.trainer.label'))
                ->formatStateUsing(fn ($state, $record) => $record->trainer?->display_name ?? $state)
                ->searchable(['name', 'name_ar'])
                ->sortable(),
                TextColumn::make('closed_at')
                    ->label(__('resources.lifecycle.closed_at'))
                    ->date()
                    ->badge()
                    ->color('gray')
                    ->placeholder(__('resources.lifecycle.open'))
                    ->sortable(),
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
                // Closed groups are hidden by default; pick "closed" or "all" to consult or edit them
                TernaryFilter::make('closed_at')
                    ->label(__('resources.lifecycle.status'))
                    ->nullable()
                    ->trueLabel(__('resources.lifecycle.closed'))
                    ->falseLabel(__('resources.lifecycle.open'))
                    ->placeholder(__('resources.lifecycle.all'))
                    ->default(false),
            ])
            ->recordActions([
                EditAction::make(),
                Lifecycle::closeGroup(),
                Lifecycle::reopenGroup(),
                Lifecycle::deleteIfUnused(),
            ]);
    }
}
