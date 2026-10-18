<?php

namespace App\Filament\Resources\Sports\Tables;

use App\Filament\Actions\Lifecycle;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label(__('resources.sport.name'))
                ->searchable(),
                TextColumn::make('name_ar')
                ->label(__('resources.sport.name_arabic'))
                    ->searchable(),
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
                // only while no group uses it
                Lifecycle::deleteIfUnused(),
            ]);
    }
}
