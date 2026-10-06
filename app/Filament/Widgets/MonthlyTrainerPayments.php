<?php

namespace App\Filament\Widgets;

use App\Models\Trainer;
use App\Models\TrainerPayout;
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

class MonthlyTrainerPayments extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Trainer::query()->with([
                'groups.trainees',
                // Only the current month's payout, with its installments already summed
                'payouts' => fn($query) => $query
                    ->whereDate('month_key', now()->startOfMonth())
                    ->withSum('installments', 'amount'),
            ]))
            ->heading(__('resources.trainer.monthly_payments'))
            ->modelLabel(__('resources.trainer.modelLabel'))
            ->pluralModelLabel(__('resources.trainer.pluralModelLabel'))
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('resources.trainer.name'))
                    ->searchable(['name', 'name_ar']),
                TextColumn::make('salary_type')
                    ->label(__('resources.trainer.salary_type'))
                    ->formatStateUsing(fn(string $state): string => __("resources.trainer.{$state}")),
                TextColumn::make('trainees_count')
                    ->label(__('resources.trainer.trainees_count'))
                    ->alignCenter()
                    ->state(fn(Trainer $record): string => $record->calculateMonthlyPayout()['trainees_count'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('groups_count')
                    ->label(__('resources.trainer.groups_count'))
                    ->alignCenter()
                    ->state(fn(Trainer $record): string => $record->calculateMonthlyPayout()['groups_count'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('monthly_payout')
                    ->label(__('resources.trainer.calculated_payout'))
                    ->state(fn(Trainer $record): string => self::money(self::monthSummary($record, now())['expected']))
                    ->color('success'),
                TextColumn::make('paid_this_month')
                    ->label(__('resources.payment.amount_paid'))
                    ->state(fn(Trainer $record): string => self::money(self::monthSummary($record, now())['paid'])),
                TextColumn::make('remaining_this_month')
                    ->label(__('resources.payment.remaining_amount'))
                    ->state(fn(Trainer $record): string => self::money(self::monthSummary($record, now())['remaining']))
                    ->color(fn(Trainer $record): string => self::monthSummary($record, now())['remaining'] > 0 ? 'danger' : 'success'),
                TextColumn::make('payout_status')
                    ->label(__('resources.payment.status.label'))
                    ->badge()
                    ->state(fn(Trainer $record): string => self::monthSummary($record, now())['status'])
                    ->formatStateUsing(fn(string $state): string => __("resources.payment.status.{$state}"))
                    ->color(fn(string $state): string => match ($state) {
                        TrainerPayout::STATUS_PAID => 'success',
                        TrainerPayout::STATUS_PARTIAL => 'warning',
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
                $this->makeTrainerPaymentAction(),
                $this->makeReceiptsAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    /**
     * Expected / paid / remaining for a trainer's month. Before the first installment
     * there is no payout yet, so the expected amount is the live calculation.
     */
    public static function monthSummary(Trainer $record, $month): array
    {
        $month = Carbon::parse($month)->startOfMonth();

        // Use the eager-loaded current-month payout when available
        $payout = $month->isSameMonth(now()) && $record->relationLoaded('payouts')
            ? $record->payouts->first()
            : $record->payoutFor($month);

        if (! $payout) {
            $expected = (float) $record->calculateMonthlyPayout()['total_fees_this_month'];

            return [
                'expected' => $expected,
                'paid' => 0.0,
                'remaining' => $expected,
                'status' => $expected > 0 ? TrainerPayout::STATUS_UNPAID : TrainerPayout::STATUS_PAID,
            ];
        }

        return [
            'expected' => (float) $payout->expected_amount,
            'paid' => $payout->paidAmount(),
            'remaining' => $payout->remainingAmount(),
            'status' => $payout->status,
        ];
    }

    private static function money(float $amount): string
    {
        return number_format($amount, 2) . ' MAD';
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
            ->schema([
                Section::make(__('resources.trainer.payment_details'))
                    ->description(__('resources.trainer.current_balance_and_payment_entry'))
                    ->schema([
                        TextEntry::make('trainer_name')
                            ->label(__('resources.trainee.name'))
                            ->state(fn(Trainer $record): string => $record->display_name),

                        // Changing the month refreshes the balance below
                        DatePicker::make('applies_to_date')
                            ->label(__('resources.payment.applies_to_date'))
                            ->default(now()->startOfMonth()->toDateString())
                            ->displayFormat('F Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->required()
                            ->live(),

                        TextEntry::make('amount_due')
                            ->label(__('resources.payment.amount_due'))
                            ->state(fn(Trainer $record, Get $get): string => self::money(self::monthSummary($record, $get('applies_to_date') ?? now())['expected'])),

                        TextEntry::make('already_paid')
                            ->label(__('resources.payment.amount_paid'))
                            ->state(fn(Trainer $record, Get $get): string => self::money(self::monthSummary($record, $get('applies_to_date') ?? now())['paid'])),

                        TextEntry::make('remaining')
                            ->label(__('resources.payment.remaining_amount'))
                            ->state(fn(Trainer $record, Get $get): string => self::money(self::monthSummary($record, $get('applies_to_date') ?? now())['remaining'])),

                        TextInput::make('amount_paid')
                            ->label(__('resources.trainer.amount_to_pay_now'))
                            ->numeric()
                            ->prefix('MAD')
                            ->placeholder('0.00')
                            ->minValue(0.01)
                            ->required()
                            ->minValue(0.01)
                            // Can't pay more than what is left for the selected month
                            ->maxValue(fn(Trainer $record, Get $get): float => self::monthSummary($record, $get('applies_to_date') ?? now())['remaining'])
                            ->default(fn(Trainer $record): float => self::monthSummary($record, now())['remaining'])
                            ->validationMessages([
                                'max' => fn(Trainer $record, Get $get): string => __('resources.trainer.amount_exceeds_remaining', [
                                    'remaining' => self::money(self::monthSummary($record, $get('applies_to_date') ?? now())['remaining']),
                                ]),
                                'min' => __('resources.trainer.amount_must_be_positive'),
                            ])
                            ->hint(__('resources.trainer.enter_full_or_partial_amount')),

                        Textarea::make('notes')
                            ->label(__('resources.notes'))
                            ->placeholder('...'),
                    ])
            ])
            ->modalSubmitActionLabel(__('resources.actions.pay_now')) // Renames the main action button
            ->modalCancelActionLabel(__('resources.actions.cancel')) // Renames the cancel button
            ->action(function (array $data, Trainer $record): void {
                $installment = $record->recordPayoutInstallment($data['applies_to_date'], (float) $data['amount_paid'], $data['notes'] ?? null);

                Notification::make()
                    ->title(__('resources.messages.payment_recorded_successfully'))
                    ->body(__('resources.messages.payment_recorded_body', [
                        'amount_paid' => $data['amount_paid'],
                        'trainer_name' => $record->display_name,
                    ]))
                    ->actions([
                        Action::make('print_receipt')
                            ->label(__('resources.trainer.print_receipt'))
                            ->icon('heroicon-o-printer')
                            ->url(route('filament.admin.trainer-payouts.receipt', $installment), shouldOpenInNewTab: true),
                    ])
                    ->persistent()
                    ->success()
                    ->send();
            });
    }

    /**
     * Lists the trainer's paid installments so any receipt can be (re)printed.
     */
    public static function makeReceiptsAction(): Action
    {
        return Action::make('payoutReceipts')
            ->label('')
            ->tooltip(__('resources.trainer.payout_receipts'))
            ->color('gray')
            ->icon('heroicon-o-printer')
            ->iconSize(IconSize::Large)
            ->modalHeading(fn(Trainer $record): string => __('resources.trainer.payout_receipts') . ' - ' . $record->display_name)
            ->modalWidth('lg')
            ->modalContent(fn(Trainer $record) => view('filament.widgets.trainer-payout-receipts', [
                'payouts' => $record->payouts()
                    ->with(['installments' => fn($query) => $query->latest('paid_at')])
                    ->withSum('installments', 'amount')
                    ->has('installments')
                    ->latest('month_key')
                    ->limit(12)
                    ->get(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('resources.actions.cancel'));
    }
}
