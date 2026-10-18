<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\Resources\Discounts\Tables\DiscountsTable;
use App\Models\Discount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 9;

    public static function getPluralModelLabel(): string
    {
        return __('resources.discount.pluralModelLabel');
    }

    public static function getModelLabel(): string
    {
        return __('resources.discount.modelLabel');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.discount.navigationLabel');
    }

    public static function getNavigationGroup(): string
    {
        return __('resources.navigationGroups.subscriptions');
    }

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
        ];
    }
}
