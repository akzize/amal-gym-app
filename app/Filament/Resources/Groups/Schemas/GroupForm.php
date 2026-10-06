<?php

namespace App\Filament\Resources\Groups\Schemas;

use App\Models\Trainer;
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
                    ->label(__('resources.group.name'))
                    ->required(),
                Select::make('sport_id')
                    ->label(__('resources.sport.label'))
                    ->relationship('sport', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('resources.sport.name'))

                            ->required(),
                        TextInput::make('name_ar')
                            ->label(__('resources.sport.name_arabic'))

                            ->required(),
                    ]),
                Select::make('trainer_id')
                    ->label(__('resources.trainer.label'))
                    ->relationship('trainer', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Trainer $record) => $record->display_name)
                    ->searchable(['name', 'name_ar'])
                    ->preload(),

                TextInput::make('monthly_fee')
                    ->label(__('resources.payment.monthly_fee'))

                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('insurance_fee')
                    ->label(__('resources.payment.insurance.all'))

                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('insurance_profit')
                    ->label(__('resources.payment.insurance.net'))

                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('registration_fee')
                    ->label(__('resources.payment.registratin'))

                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('max_capacity')
                    ->numeric()
                    ->default(20)
                    ->label(__('resources.group.max_capacity'))
                    ->required(),
                Select::make('association_id')
                ->label(__('resources.association.singule'))
                    ->relationship('association', 'name'),
            ]);
    }
}
