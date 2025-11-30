<?php

namespace App\Filament\Resources\Associations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssociationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label(__('resources.association.logo'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('resources.association.name'))
                    ->searchable(),
                TextColumn::make('name_arabic')
                    ->label(__('resources.association.name_arabic'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('resources.association.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('resources.association.email'))
                    ->searchable(),
                TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('resources.association.address'))
                    ->searchable(),
                TextColumn::make('website')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('resources.association.website'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('resources.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('resources.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
