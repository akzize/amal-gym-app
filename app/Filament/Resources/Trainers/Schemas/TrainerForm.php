<?php

namespace App\Filament\Resources\Trainers\Schemas;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Login Credentials')
                    ->afterHeader([
                        Toggle::make('edit_login')
                            ->label('Edit Login Credentials')
                            ->default(false)
                            ->reactive()
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ])
                    ->schema([
                        Hidden::make('user_id'),
                        TextInput::make('name')
                            ->disabled(fn(callable $get) => !$get('edit_login'))
                            ->dehydrated(fn(callable $get) => $get('edit_login'))
                            ->required(fn(callable $get) => $get('edit_login')),
                        TextInput::make('user.email')
                            ->label('Email')
                            ->disabled(fn(callable $get) => !$get('edit_login'))
                            ->dehydrated(fn(callable $get) => $get('edit_login'))
                            ->email()
                            ->required(fn(callable $get) => $get('edit_login'))
                            ->unique(User::class, 'email', fn($record) => $record?->user),

                        TextInput::make('user.password')
                            ->label('Password')
                            ->disabled(fn(callable $get) => !$get('edit_login'))
                            ->dehydrated(fn(callable $get) => $get('edit_login'))
                            ->password()
                            ->revealable(true)
                            ->copyable(copyMessage: 'Copied!', copyMessageDuration: 1500)
                            ->required(fn(callable $get) => $get('edit_login')),
                    ]),

                Section::make('Payment info')
                    ->schema([
                        Select::make('salary_type')
                            ->options([
                                'fixed' => 'Fixed',
                                'per_group' => 'Per group',
                                'per_trainee' => 'Per trainee',
                                'none' => 'None',
                            ])
                            ->required(),
                        TextInput::make('salary_amount')
                            ->numeric(),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->default('active')
                            ->required(),
                    ]),

                // groups 
                Select::make('groups')
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
