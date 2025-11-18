<?php

namespace App\Filament\Resources\Trainees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TraineeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->avatar()
                            ->openable()
                            ->disk('public')
                            ->directory('images/trainees'),
                        TextInput::make('full_name')
                            ->required(),
                    ]),
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('full_arabic_name'),
                        Select::make('gender')
                            ->options(['male' => 'Male', 'female' => 'Female'])
                            ->required(),
                        DatePicker::make('dob')
                            ->required(),
                        TextInput::make('phone')
                            ->tel()
                            ->required(),
                    ]),
                Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
