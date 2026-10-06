<?php

namespace App\Filament\Resources\Trainers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
