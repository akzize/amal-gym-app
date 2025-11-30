<?php

namespace App\Filament\Widgets;

use App\Models\Trainee;
use App\Models\Trainer;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MonthlyTrainerPayments extends TableWidget
{
    protected static ?int $sort = 2;
    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Trainer::query())
            // ->header(__('resources.trainer.monthly_payments'))
            ->heading(__('resources.trainer.monthly_payments'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources.trainer.name'))
                    ->searchable(),
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
                        // Call the method we created on the Trainer model
                        $payout = $record->calculateMonthlyPayout()['total_fees_this_month'];
                        // Format the output nicely
                        return number_format($payout, 2) . ' MAD';
                    })
                    ->sortable() // You might need a custom sort for calculated columns
                    ->color('success'),
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
                            ->default(Carbon::now()->format('F Y')),
                            // ->state(Carbon::now()->format('F Y')), // Set current month

                        // Assuming these attributes exist on your Trainer model for simplicity
                        TextEntry::make('amount_due')
                            ->label(__('resources.payment.amount_due'))
                            ->state(fn(Model $record): string => number_format($record->calculateMonthlyPayout()['total_fees_this_month'], 2) . ' MAD'),

                        TextEntry::make('amount_paid')
                            ->label(__('resources.payment.amount_paid'))
                            ->state(fn(Model $record): string => number_format($record->paid_amount, 2) . ' MAD'),

                        TextEntry::make('remaining')
                            ->label(__('resources.payment.remaining_amount'))
                            // Calculate remaining balance dynamically
                            ->state(fn(Model $record): string => number_format($record->total_due - $record->paid_amount, 2) . ' MAD'),

                        // The main input field for the admin
                        TextInput::make('amount_paid')
                            ->label(__('resources.trainer.amount_to_pay_now'))
                            ->numeric()
                            ->prefix('MAD')
                            ->placeholder('0.00')
                            ->required()
                            // ->maxValue(fn(Model $record) => $record->total_due - $record->paid_amount) // Restrict input to remaining amount
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
                // dd($data);
                // Logic to save the payment record to your database
                $total_fees_this_month = $record->calculateMonthlyPayout()['total_fees_this_month'];
                $status = $data['amount_paid'] >= $total_fees_this_month ? 'paid' : 'partial';
                $record->payments()->create([
                    'amount_paid' => $data['amount_paid'],
                    'expected_amount' => $total_fees_this_month,
                    'status' => $status,
                    'applies_to_date' => $data['applies_to_date'],
                    'paid_at' => today(),
                    'notes' => $data['notes'],
                ]);

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
