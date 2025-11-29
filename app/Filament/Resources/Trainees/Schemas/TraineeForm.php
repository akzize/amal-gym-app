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
                            ->label(__('resources.trainee.avatar'))
                            ->image()
                            ->avatar()
                            ->openable()
                            ->disk('public')
                            ->directory('images/trainees')
                            ->columnSpanFull(),
                    ]),
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('full_name')
                            ->label(__('resources.trainee.full_name'))
                            ->required(),
                        TextInput::make('full_arabic_name')
                            ->label(__('resources.trainee.full_arabic_name')),
                        Select::make('gender')
                            ->options(['male' => 'Male', 'female' => 'Female'])
                            ->label(__('resources.trainee.gender'))
                            ->required(),
                        DatePicker::make('dob')
                            ->label(__('resources.trainee.dob'))

                            ->required(),
                        TextInput::make('phone')
                            ->label(__('resources.trainee.phone'))

                            ->tel()
                            ->required(),
                    ]),
                Textarea::make('address')
                    ->label(__('resources.trainee.address'))

                    ->required()
                    ->columnSpanFull(),

                Select::make('group_id')
                    ->label('Group')
                    ->label(__('resources.trainee.group'))

                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ]);
    }
}
