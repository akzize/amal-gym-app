<?php

namespace App\Filament\Resources\Associations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssociationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('resources.association.name'))
                    ->required(),
                TextInput::make('name_arabic')
                    ->label(__('resources.association.name_arabic')),
                Textarea::make('description')
                    ->label(__('resources.association.description'))
                    ->columnSpanFull(),
                FileUpload::make('logo')
                    ->label(__('resources.association.logo'))
                    ->default('images/default-assosciatio-logo.png'),
                TextInput::make('phone')
                    ->label(__('resources.association.phone'))
                    ->tel(),
                TextInput::make('email')
                    ->label(__('resources.association.email'))
                    ->email(),
                TextInput::make('address')
                    ->label(__('resources.association.address')),
                TextInput::make('website')
                    ->label(__('resources.association.website'))
                    ->url(),
            ]);
    }
}
