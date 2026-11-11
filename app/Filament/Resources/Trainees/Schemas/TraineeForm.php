<?php

namespace App\Filament\Resources\Trainees\Schemas;

use App\Models\Trainee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                            ->label(__('resources.trainee.full_arabic_name'))
                            ->required(),
                        // NOM on the insurance export
                        TextInput::make('last_name')
                            ->label(__('resources.trainee.last_name')),
                        TextInput::make('cin')
                            ->label(__('resources.trainee.cin'))
                            ->maxLength(20),
                        Select::make('gender')
                            ->options(['male' => 'Male', 'female' => 'Female'])
                            ->label(__('resources.trainee.gender'))
                            ->required(),
                        DatePicker::make('dob')
                            ->label(__('resources.trainee.dob')),
                        TextInput::make('phone')
                            ->label(__('resources.trainee.phone'))
                            ->tel(),
                    ]),
                Textarea::make('address')
                    ->label(__('resources.trainee.address'))
                    ->columnSpanFull(),

                Select::make('group_id')
                    ->label('Group')
                    ->label(__('resources.trainee.group'))

                    // open groups, plus closed ones the trainee is already in (saving syncs this list)
                    ->relationship('groups', 'name', modifyQueryUsing: fn (Builder $query, ?Trainee $record) => $query
                        ->where(fn (Builder $groups) => $groups
                            ->whereNull('groups.closed_at')
                            ->when($record, fn (Builder $groups) => $groups->orWhereIn('groups.id', $record->groups()->pluck('groups.id')))))
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ]);
    }
}
