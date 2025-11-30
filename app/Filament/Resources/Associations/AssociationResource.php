<?php

namespace App\Filament\Resources\Associations;

use App\Filament\Resources\Associations\Pages\CreateAssociation;
use App\Filament\Resources\Associations\Pages\EditAssociation;
use App\Filament\Resources\Associations\Pages\ListAssociations;
use App\Filament\Resources\Associations\Schemas\AssociationForm;
use App\Filament\Resources\Associations\Tables\AssociationsTable;
use App\Models\Association;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssociationResource extends Resource
{
    protected static ?string $model = Association::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getPluralModelLabel(): string
    {
        return __('resources.association.pluralModelLabel');
    }

    public static function getModelLabel(): string
    {
        return __('resources.association.modelLabel');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.association.navigationLabel');
    }

    public static function getNavigationGroup(): string
    {
        return __('resources.navigationGroups.salle_association');
    }

    public static function form(Schema $schema): Schema
    {
        return AssociationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssociationsTable::configure($table);
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
            'index' => ListAssociations::route('/'),
            'create' => CreateAssociation::route('/create'),
            'edit' => EditAssociation::route('/{record}/edit'),
        ];
    }
}
