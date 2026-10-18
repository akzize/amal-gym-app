<?php

namespace App\Filament\Resources\Discounts\Schemas;

use App\Models\Discount;
use App\Models\PaymentType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('resources.discount.name'))
                    ->required(),
                TextInput::make('name_ar')
                    ->label(__('resources.discount.name_ar'))
                    ->required(),
                Select::make('type')
                    ->label(__('resources.discount.type'))
                    ->options([
                        Discount::TYPE_PERCENT => __('resources.discount.types.percent'),
                        Discount::TYPE_FIXED => __('resources.discount.types.fixed'),
                    ])
                    ->default(Discount::TYPE_PERCENT)
                    ->live()
                    ->required(),
                TextInput::make('value')
                    ->label(__('resources.discount.value'))
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn(callable $get) => $get('type') === Discount::TYPE_PERCENT ? 100 : null)
                    ->suffix(fn(callable $get) => $get('type') === Discount::TYPE_PERCENT ? '%' : 'MAD')
                    ->required(),
                Select::make('payment_type_id')
                    ->label(__('resources.discount.payment_type'))
                    ->relationship('paymentType', 'name')
                    ->getOptionLabelFromRecordUsing(fn(PaymentType $record) => $record->name_ar ?: $record->name)
                    ->placeholder(__('resources.discount.all_payment_types')),
                Section::make(__('resources.discount.conditions'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('min_months')
                            ->label(__('resources.discount.min_months'))
                            ->helperText(__('resources.discount.min_months_help'))
                            ->numeric()
                            ->integer()
                            ->minValue(2),
                        Toggle::make('new_members_only')
                            ->label(__('resources.discount.new_members_only'))
                            ->helperText(__('resources.discount.new_members_only_help', ['days' => Discount::NEW_MEMBER_DAYS])),
                        DatePicker::make('valid_from')
                            ->label(__('resources.discount.valid_from'))
                            ->native(false),
                        DatePicker::make('valid_until')
                            ->label(__('resources.discount.valid_until'))
                            ->native(false)
                            ->afterOrEqual('valid_from'),
                    ]),
                Toggle::make('is_automatic')
                    ->label(__('resources.discount.is_automatic'))
                    ->helperText(__('resources.discount.is_automatic_help')),
                Toggle::make('is_active')
                    ->label(__('resources.discount.is_active'))
                    ->default(true),
            ]);
    }
}
