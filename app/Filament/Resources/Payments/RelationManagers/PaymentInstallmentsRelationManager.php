<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Date;
use Livewire\Component;

class PaymentInstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount_paid')
                    ->label(__('resources.payment.amount_paid'))
                    ->columnSpanFull()
                    ->required()
                    ->numeric()
                    ->rules([
                        'min:0',
                        fn(): \Closure => function ($attribute, $value, $fail) {
                            $payment = $this->getOwnerRecord();
                            if (! $payment) {
                                return;
                            }

                            // Prefer model field payment_remaining if present, otherwise compute it
                            $remaining = $payment->payment_remaining ?? ($payment->amount_due - $payment->amount_paid);

                            if ($value > $remaining) {
                                $fail("The amount cannot be greater than the remaining payment ({$remaining} MAD).");
                            }
                        },
                    ]),

                DatePicker::make('paid_at')
                    ->label(__('resources.payment.paid_at'))
                    ->columnSpanFull()
                    ->required()
                    ->native(false)
                    ->default(Date::now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_id')
            ->modelLabel(__('resources.payment.modelLabel'))
            ->pluralModelLabel(__('resources.payment.pluralModelLabel'))
            ->heading(__('resources.payment.pluralModelLabel'))
            ->columns([
                TextColumn::make('amount_paid')
                    ->label(__('resources.payment.amount_paid'))
                    ->money('MAD', true)
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label(__('resources.payment.paid_at'))
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('md')
                    ->after(function ($livewire) {
                        // $record is the created installment
                        $payment = $this->getOwnerRecord();
                        $payment->amount_paid = $payment->installments()->sum('amount_paid');

                        // Update payment status 
                        $payment->status = $payment->amount_paid >= $payment->amount_due ? 'paid' : 'partial';
                        if ($payment->amount_paid == 0) $payment->status = 'unpaid';
                        $payment->save();

                        $livewire->dispatch('refreshPaymentComponents');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('md')
                    ->hidden(true)
                    ->schema([
                        TextInput::make('amount_paid')
                            ->columnSpanFull()
                            ->required()
                            ->numeric()
                            ->rules([
                                'min:0',
                                fn(): \Closure => function ($attribute, $value, $fail) {
                                    $payment = $this->getOwnerRecord();
                                    if (! $payment) {
                                        return;
                                    }

                                    // Prefer model field payment_remaining if present, otherwise compute it
                                    $remaining = $payment->payment_remaining ?? ($payment->amount_due - $payment->amount_paid);

                                    // Add back the current installment amount to remaining for edit validation
                                    $currentInstallment = $this->record ? $this->record->amount_paid : 0;
                                    $remaining += $currentInstallment;

                                    if ($value > $remaining) {
                                        $fail("The amount cannot be greater than the remaining payment ({$remaining} MAD).");
                                    }
                                },
                            ]),
                        DatePicker::make('paid_at')
                            ->columnSpanFull()
                            ->required()
                            ->native(false),
                    ]),
                // DissociateAction::make(),
                DeleteAction::make()
                    ->after(function (Component $livewire) {
                        // $record is the deleted installment
                        $payment = $this->getOwnerRecord();
                        $payment->amount_paid = $payment->installments()->sum('amount_paid');

                        // Update payment status 
                        $payment->status = $payment->amount_paid >= $payment->amount_due ? 'paid' : 'partial';
                        if ($payment->amount_paid == 0) $payment->status = 'unpaid';

                        $payment->save();

                        $livewire->dispatch('refreshPaymentComponents');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
