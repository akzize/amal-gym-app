<?php

namespace App\Filament\Resources\Trainees;

use App\Filament\Resources\Trainees\Pages\CreateTrainee;
use App\Filament\Resources\Trainees\Pages\EditTrainee;
use App\Filament\Resources\Trainees\Pages\ListTrainees;
use App\Filament\Resources\Trainees\Schemas\TraineeForm;
use App\Filament\Resources\Trainees\Tables\TraineesTable;
use App\Models\Trainee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TraineeResource extends Resource
{
    protected static ?string $model = Trainee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getPluralModelLabel(): string
    {
        return __('resources.trainee.pluralModelLabel');
    }

    public static function getModelLabel(): string
    {
        return __('resources.trainee.modelLabel');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.trainee.navigationLabel');
    }
    public static function getNavigationGroup(): string
    {
        return __('resources.navigationGroups.training');
    }
    public static function form(Schema $schema): Schema
    {
        return TraineeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TraineesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainees::route('/'),
            'create' => CreateTrainee::route('/create'),
            'edit' => EditTrainee::route('/{record}/edit'),
        ];
    }
}
