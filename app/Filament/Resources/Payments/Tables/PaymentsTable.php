<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('subscription_id')
                //     ->numeric()
                //     ->sortable(),
                TextColumn::make('trainee.full_name')
                    ->label(__('resources.trainee.label'))
                    ->default('Non registred Trainee')
                    // show the arabic name when the app is in arabic, fall back to the french one
                    ->formatStateUsing(fn($state, Payment $record) => App::getLocale() == 'ar'
                        ? ($record->trainee?->full_arabic_name ?: $state)
                        : $state)
                    ->sortable(),
                TextColumn::make('paymentType.name')
                    ->label(__('resources.payment.types.label'))
                    ->formatStateUsing(function ($state, Payment $record) {
                        $paymentType = $record->paymentType;
                        $selectedLang = App::getLocale();
                        $isArabicSelected = $selectedLang == 'ar';
                        // change the state to use the arabic field if ar selected
                        $state = $isArabicSelected ? $paymentType->name_ar : $state;
                        if ($paymentType->id == Payment::TYPE_CUSTOM) {
                            $duration_months = $record->subscription->duration_months;
                            $unit = $isArabicSelected ? 'أشهر' : Str::plural('month', $duration_months);
                            $state = $duration_months . ' ' . $unit;
                        }

                        $state = Str::of($state)->replace('_', ' ');
                        return $state;
                    })
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label(__('resources.payment.amount_due'))
                    ->numeric(locale: 'en')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label(__('resources.payment.amount_paid'))
                    ->numeric(locale: 'en')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('resources.payment.status.label'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => __("resources.payment.status.{$state}"))
                    ->color(fn(string $state): string => match ($state) {
                        Payment::STATUS_PAID => 'success',
                        Payment::STATUS_UNPAID => 'danger',
                        Payment::STATUS_PARTIAL => 'warning',
                        Payment::STATUS_FREE => 'gray',
                    }),
                TextColumn::make('payment_date')
                    ->label(__('resources.payment.payment_date'))
                    ->dateTime()
                    ->formatStateUsing(fn($state) => $state ? date('d-m-Y', strtotime($state)) : null)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->color('success')
                    ->iconSize('md'),
                DeleteAction::make()
                    ->label('')
                    ->color('danger')
                    ->iconSize('md'),
                Action::make('print_receipt')
                    ->label('')
                    ->icon(Heroicon::Printer)
                    ->iconSize('md')
                    ->url(fn(Payment $record) => route('filament.admin.payments.receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
