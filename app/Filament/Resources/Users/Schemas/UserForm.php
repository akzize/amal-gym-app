<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('resources.user.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('resources.user.email'))
                    ->email()
                    ->required(),
                Select::make('roles')
                    ->label(__('resources.user.role'))
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple()
                    ->maxItems(1)
                    ->searchable(),
                // DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->label(__('resources.user.password'))
                    ->password()
                    ->copyable()
                    ->revealable(true)
                    ->required(),
            ]);
    }
}
