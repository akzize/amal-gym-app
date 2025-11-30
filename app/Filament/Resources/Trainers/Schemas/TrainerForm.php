<?php

namespace App\Filament\Resources\Trainers\Schemas;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TrainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.trainer.login_credentials'))
                    ->afterHeader([
                        Toggle::make('edit_login')
                            ->label(__('resources.trainer.edit_login_credentials'))
                            ->default(false)
                            ->reactive()
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ])
                    ->schema([
                        Hidden::make('user_id'),
                        TextInput::make('name')
                            ->label(__('resources.trainer.name'))
                            ->disabled(fn(callable $get, string $operation) => ($operation == 'edit' && !$get('edit_login')))
                            ->dehydrated(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create')
                            ->required(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create'),
                        TextInput::make('user.email')
                            ->label(__('resources.trainer.email'))
                            ->disabled(fn(callable $get, string $operation) => ($operation == 'edit' && !$get('edit_login')))
                            ->dehydrated(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create')
                            ->email()
                            ->required(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create')
                            ->unique(User::class, 'email', fn($record) => $record?->user),

                        TextInput::make('user.password')
                            ->label(__('resources.trainer.password'))
                            ->disabled(fn(callable $get, string $operation) => ($operation == 'edit' && !$get('edit_login')))
                            ->dehydrated(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create')
                            ->password()
                            ->revealable(true)
                            ->copyable(copyMessage: 'Copied!', copyMessageDuration: 1500)
                            ->required(fn(callable $get, string $operation) => $get('edit_login') || $operation == 'create'),
                    ]),

                Section::make(__('resources.trainer.Payment info'))
                    ->schema([
                        // 'salary_type' label will be automatically translated
                        Select::make('salary_type')
                            ->label(__('resources.trainer.salary_type'))
                            ->reactive()
                            ->options([
                                'fixed' => __('resources.trainer.fixed'),
                                'percentage' => __('resources.trainer.percentage'),
                            ])
                            ->required(),
                        // 'salary_amount' label will be automatically translated
                        TextInput::make('salary_amount')
                            // ->label(__('resources.trainer.salary_amount'))
                            ->label(function (Get $get) {
                                $type = $get('salary_type');

                                if ($type == 'percentage') {
                                    return __('resources.trainer.payment_percent');
                                }

                                return __('resources.trainer.salary_amount');
                            })
                            ->visible(fn(Get $get): bool => $get('salary_type') !== 'none')
                            ->suffix(function(Get $get){
                                $type = $get('salary_type');
                                return $type == 'percentage' ? '%' : 'MAD';
                            })
                            ->reactive()
                            ->numeric(),

                        // 'status' label will be automatically translated
                        Select::make('status')
                            ->options(['active' => __('resources.trainer.active'), 'inactive' => __('resources.trainer.inactive')]) // Added __()
                            ->default('active')
                            ->required(),
                    ]),

                // groups 
                Select::make('groups')
                    ->label(__('resources.trainer.groups')) // Added __() for explicit clarity, although often automatic
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label(__('resources.trainer.notes')) // Added __() for explicit clarity
                    ->columnSpanFull(),
            ]);
    }
}
