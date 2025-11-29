<?php

namespace App\Filament\Resources\Sports\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('resources.sport.name'))
                    ->required(),
                TextInput::make('name_ar')
                    ->label(__('resources.sport.name_arabic'))
                    ->required(),
            ]);
    }
}
