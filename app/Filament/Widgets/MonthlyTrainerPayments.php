<?php

namespace App\Filament\Widgets;

use App\Models\Trainer;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyTrainerPayments extends TableWidget
{
    protected static ?int $sort = 2;
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Trainer::query()->where('status', 'active'))
            ->heading(__('resources.trainer.monthly_payments'))
            ->modelLabel(__('resources.trainer.modelLabel'))
            ->pluralModelLabel(__('resources.trainer.pluralModelLabel'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources.trainer.name'))
                    ->searchable(),
                TextColumn::make('paid_this_month')
                    ->label(__('resources.trainer.paid_this_month'))
                    ->state(fn (Trainer $record): string => number_format($record->monthlyPayoutSummary()['amount_paid'], 2) . ' MAD'),
                TextColumn::make('remaining_this_month')
                    ->label(__('resources.trainer.remaining_this_month'))
                    ->state(fn (Trainer $record): string => number_format($record->monthlyPayoutSummary()['remaining_amount'], 2) . ' MAD'),
                TextColumn::make('salary_type')
                    ->label(__('resources.trainer.salary_type'))
                    ->searchable(),
                TextColumn::make('trainees_count')
                    ->label(__('resources.trainer.trainees_count'))
                    ->alignCenter()
                    ->state(function (Trainer $record): string {
                        // Call the method we created on the Trainer model
                        $count = $record->calculateMonthlyPayout()['trainees_count'];

                        return $count;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('groups_count')
                    ->label(__('resources.trainer.groups_count'))
                    ->alignCenter()
                    ->state(function (Trainer $record): string {
                        // Call the method we created on the Trainer model
                        $count = $record->calculateMonthlyPayout()['groups_count'];

                        return $count;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                // Use a computed column to show the calculated payout
                TextColumn::make('monthly_payout')
                    ->label(__('resources.trainer.calculated_payout'))
                    ->state(function (Trainer $record): string {
                        return number_format($record->monthlyPayoutSummary()['expected_amount'], 2) . ' MAD';
                    })
                    ->color('success'),
                TextColumn::make('payout_status')
                    ->label(__('resources.payment.status.label'))
                    ->state(fn (Trainer $record): string => $record->monthlyPayoutSummary()['status'])
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    }),
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
            ->headerActions([
                //
            ])
            ->recordActions([
                $this->makeTrainerPaymentAction()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function makeTrainerPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label('')
            ->color('success')
            ->icon('heroicon-o-currency-dollar')
            ->iconSize(IconSize::Large)
            ->modalHeading(__('resources.trainer.trainer_payment_processing'))
            ->modalWidth('md') // Adjust modal size
            ->hidden(fn (Trainer $record): bool => $record->monthlyPayoutSummary()['remaining_amount'] <= 0)
            // Define the structure of the modal form
            ->schema([
                // You can use a Section for better grouping visually
                Section::make(__('resources.trainer.payment_details'))
                    ->description(__('resources.trainer.current_balance_and_payment_entry'))
                    ->schema([
                        // Read-only info based on the current record (Trainer Model)
                        TextEntry::make('trainer_name')
                            ->label(__('resources.trainee.name'))
                            ->state(fn(Model $record): string => $record->name),

                        DatePicker::make('applies_to_date')
                            ->label(__('resources.payment.applies_to_date'))
                            ->default(Carbon::now()->startOfMonth())
                            ->displayFormat('F Y')
                            ->live()
                            ->required(),

                        // Assuming these attributes exist on your Trainer model for simplicity
                        TextEntry::make('amount_due')
                            ->label(__('resources.payment.amount_due'))
                            ->state(fn (Trainer $record, Get $get): string => number_format(
                                $record->monthlyPayoutSummary($get('applies_to_date'))['expected_amount'], 2
                            ) . ' MAD'),

                        TextEntry::make('amount_paid')
                            ->label(__('resources.payment.amount_paid'))
                            ->state(fn (Trainer $record, Get $get): string => number_format(
                                $record->monthlyPayoutSummary($get('applies_to_date'))['amount_paid'], 2
                            ) . ' MAD'),

                        TextEntry::make('remaining')
                            ->label(__('resources.payment.remaining_amount'))
                            ->state(fn (Trainer $record, Get $get): string => number_format(
                                $record->monthlyPayoutSummary($get('applies_to_date'))['remaining_amount'], 2
                            ) . ' MAD'),

                        // The main input field for the admin
                        TextInput::make('amount_paid')
                            ->label(__('resources.trainer.amount_to_pay_now'))
                            ->numeric()
                            ->prefix('MAD')
                            ->placeholder('0.00')
                            ->minValue(0.01)
                            ->required()
                            ->hint(__('resources.trainer.enter_full_or_partial_amount')),

                        // Optional Notes field
                        Textarea::make('notes')
                            ->label(__('resources.notes'))
                            ->placeholder('...'),
                    ])
            ])
            ->modalSubmitActionLabel(__('resources.actions.pay_now')) // Renames the main action button
            ->modalCancelActionLabel(__('resources.actions.cancel')) // Renames the cancel button

            // This is what happens when "Pay Now" is clicked
            ->action(function (array $data, Model $record): void {
                $month = Carbon::parse($data['applies_to_date'])->startOfMonth();
                $amount = round((float) $data['amount_paid'], 2);

                DB::transaction(function () use ($record, $month, $amount, $data): void {
                    // Serialize payouts for this trainer so concurrent submissions cannot overpay.
                    $trainer = Trainer::query()->lockForUpdate()->findOrFail($record->getKey());
                    $summary = $trainer->monthlyPayoutSummary($month);

                    if ($amount <= 0 || $amount > $summary['remaining_amount']) {
                        throw ValidationException::withMessages([
                            'amount_paid' => __('Enter an amount up to the remaining balance (:amount MAD).', [
                                'amount' => number_format($summary['remaining_amount'], 2),
                            ]),
                        ]);
                    }

                    $periodStart = $month->toDateString();
                    $periodEnd = $month->copy()->endOfMonth()->toDateString();
                    $newPaidTotal = $summary['amount_paid'] + $amount;
                    $status = $newPaidTotal >= $summary['expected_amount'] ? 'paid' : 'partial';

                    $trainer->payments()->create([
                        'amount_paid' => $amount,
                        'expected_amount' => $summary['expected_amount'],
                        'status' => $status,
                        'applies_to_date' => $periodStart,
                        'paid_at' => today(),
                        'notes' => $data['notes'] ?? null,
                    ]);

                    // Keep the stored period status aligned with the cumulative payout total.
                    $trainer->payments()
                        ->whereBetween('applies_to_date', [$periodStart, $periodEnd])
                        ->update(['status' => $status]);
                });

                Notification::make()
                    ->title(__('resources.messages.payment_recorded_successfully'))
                    ->body(__('resources.messages.payment_recorded_body', [
                        'amount_paid' => $data['amount_paid'],
                        'trainer_name' => $record->name,
                    ]))
                    ->success()
                    ->send();
            });
    }
}
