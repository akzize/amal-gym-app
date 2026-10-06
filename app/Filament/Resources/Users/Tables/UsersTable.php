<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Arabic name, latin name as fallback
                TextColumn::make('display_name')
                    ->label(__('resources.user.name'))
                    ->searchable(['name', 'name_ar'])
                    ->description(fn(User $record): ?string => $record->name_ar ? $record->name : null),
                TextColumn::make('email')
                    ->label(__('resources.user.email'))
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('resources.user.role'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => RoleResource::labelFor($state)),
                TextColumn::make('deleted_at')
                    ->label(__('resources.user.deleted_at'))
                    ->dateTime()
                    ->badge()
                    ->color('danger')
                    ->placeholder('—')
                    ->toggleable(),
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // Soft delete only: the account is disabled and can be restored
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Per-record policy check so your own account is skipped
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
