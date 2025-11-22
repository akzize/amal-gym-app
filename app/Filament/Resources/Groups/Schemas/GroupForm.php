<?php

namespace App\Filament\Resources\Groups\Schemas;

use Dom\Text;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('sport_id')
                ->relationship('sport', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('name_ar')
                            ->required(),
                    ]),
                Select::make('trainer_id')
                    ->relationship('trainer', 'name'),

                TextInput::make('monthly_fee')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('insurance_fee')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('insurance_profit')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('registration_fee')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('max_capacity')
                    ->numeric()
                    ->default(20)   
                    ->required(),
            ]);
    }
}
