<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TraineesRelationManager extends RelationManager
{
    protected static string $relationship = 'trainees';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('full_arabic_name'),
                FileUpload::make('image')
                    ->image(),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female'])
                    ->required(),
                DatePicker::make('dob')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('photo_url')
                    ->label(__('resources.trainee.avatar'))
                    ->placeholder('-'),
                TextEntry::make('full_name')
                    ->label(__('resources.trainee.full_name')),
                    TextEntry::make('full_arabic_name')
                    ->label(__('resources.trainee.full_arabic_name'))
                    ->placeholder(placeholder: '-'),
                TextEntry::make('gender')
                    ->badge(),
                TextEntry::make('dob')
                    ->label(__('resources.trainee.dob'))
                    ->date(),
                TextEntry::make('phone')
                    ->label(__('resources.trainee.phone')),
                TextEntry::make('address')
                    ->label(__('resources.trainee.address'))
                    ->columnSpanFull(),
                // TextEntry::make('created_at')
                //     ->dateTime()
                //     ->placeholder('-'),
                // TextEntry::make('updated_at')
                //     ->dateTime()
                //     ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->modelLabel(__('resources.trainee.modelLabel'))
            ->pluralModelLabel(__('resources.trainee.pluralModelLabel'))
            ->heading(__('resources.trainee.pluralModelLabel'))
            ->columns([
                ImageColumn::make('photo_url')
                    ->label(__('resources.trainee.avatar'))
                    ->defaultImageUrl(url('storage/trainees/default.png'))
                    ->circular(),
                TextColumn::make('full_name')
                    ->label(__('resources.trainee.full_name'))
                    ->searchable(),
                TextColumn::make('full_arabic_name')
                    ->label(__('resources.trainee.full_arabic_name'))
                    ->searchable(),
                TextColumn::make('gender')
                    ->label(__('resources.trainee.gender'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge(),
                TextColumn::make('dob')
                    ->label(__('resources.trainee.dob'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('resources.trainee.avatar'))

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
            ->headerActions([
                AttachAction::make()
                    ->label(__('resources.trainee.actions.add_to_group'))
                    ->preloadRecordSelect()
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                DetachAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
                DeleteAction::make()
                    ->label('')
                    ->iconSize(IconSize::Medium),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
