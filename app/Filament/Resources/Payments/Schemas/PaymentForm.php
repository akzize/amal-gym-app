<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Discount;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Trainee;
use App\Support\PriceQuote;
use Dom\Text;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

use function Laravel\Prompts\select;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subscription_id')
                    ->hidden()
                    ->relationship('subscription', 'id'),
                //! i may need to add the group here later, in case the trainee has multiple groups
                Select::make('payment_type_id')
                    ->label(__('resources.payment.types.label'))
                    ->relationship('paymentType', 'name')
                    ->getOptionLabelFromRecordUsing(fn(PaymentType $record) => $record->name_ar ?: $record->name)
                    ->disabledOn('edit')
                    ->live(onBlur: true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $selected_type = $get('payment_type_id');

                        // initial update of the subscription end date
                        if ($selected_type == Payment::TYPE_INSURANCE || $selected_type == Payment::TYPE_CUSTOM) {
                            $durationMonths = $selected_type == Payment::TYPE_INSURANCE
                                ? (PaymentType::find($selected_type)?->duration_months ?? 1)
                                : 1;
                            $set('custom_duration_months', $durationMonths);
                            $set('subscription.end_date', Date::parse($get('subscription.start_date'))->addMonths($durationMonths));
                        }

                        self::syncDiscounts($get, $set);
                        self::refreshQuote($get, $set);
                    })
                    ->required(),
                // Select the group first so the trainee list can be scoped to it.
                Select::make('group_id')
                    ->label(__('resources.group.label'))
                    ->disabled(fn (string $operation) => self::lockedOnEdit($operation))
                    // closed groups are not offered for new payments (the current one still shows on edit)
                    ->options(fn (callable $get) => Group::query()
                        ->where(fn (Builder $query) => $query->whereNull('closed_at')->orWhere('groups.id', $get('group_id')))
                        ->pluck('name', 'id'))
                    ->default(fn (callable $get) => $get('default_group_id'))
                    ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation) {
                        $set('trainee_id', null);
                        // a recorded payment keeps its amounts when it is moved to another group
                        if ($operation === 'create') {
                            self::syncDiscounts($get, $set);
                            self::refreshQuote($get, $set);
                        }
                    })
                    ->live()
                    // a subscription's months are monthly payments of one group
                    ->required(fn (callable $get) => $get('payment_type_id') == Payment::TYPE_CUSTOM)
                    ->hidden(fn (callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION),
                Select::make('trainee_id')
                    ->label(__('resources.trainee.label'))
                    ->relationship('trainee', 'full_name', modifyQueryUsing: function (Builder $query, callable $get) {
                        $groupId = $get('group_id');

                        if (! $groupId) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query
                            ->whereHas('groups', fn (Builder $groupQuery) => $groupQuery->whereKey($groupId))
                            // deactivated trainees are not offered for new payments
                            ->where(fn (Builder $active) => $active->where('is_active', true)->orWhere('trainees.id', $get('trainee_id')));
                    })
                    ->getOptionLabelFromRecordUsing(fn (Trainee $record) => $record->full_arabic_name ?: $record->full_name)
                    ->searchable(['full_name', 'full_arabic_name'])
                    ->disabled(fn (callable $get, string $operation) => self::lockedOnEdit($operation) || blank($get('group_id')))
                    ->live()
                    // new-member reductions depend on the trainee
                    ->afterStateUpdated(function (callable $set, callable $get, string $operation) {
                        if ($operation === 'create') {
                            self::syncDiscounts($get, $set);
                            self::refreshQuote($get, $set);
                        }
                    })
                    ->required(fn (callable $get) => $get('payment_type_id') == Payment::TYPE_CUSTOM)
                    ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                    ->preload(),

                // field to determine how many months to add for custom subscription
                TextInput::make('custom_duration_months')
                    ->label('Custom Subscription Duration (Months)')
                    ->label(__('resources.payment.custom_duration_months'))
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->disabledOn('edit')
                    ->hiddenOn('edit')
                    ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_CUSTOM || $get('payment_type_id') == Payment::TYPE_INSURANCE)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // update the end date
                        $set('subscription.end_date', Date::parse($get('subscription.start_date'))->addMonths((int) $state));
                        // the duration picks the tier
                        self::syncDiscounts($get, $set);
                        self::refreshQuote($get, $set);
                    }),
                Grid::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // subscription start and end date
                        DatePicker::make('subscription.start_date')
                            ->label(__('resources.subscription.start_date'))
                            ->native(false)
                            ->disabledOn('edit')
                            ->afterStateHydrated(function ($state, callable $set, $livewire) {
                                $set(
                                    'subscription.start_date',
                                    $livewire->record->subscription->start_date ?? Date::now()
                                );
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // if custom duration months is set, update end date based on start date + custom months
                                $paymentTypeId = $get('payment_type_id');
                                if ($paymentTypeId == Payment::TYPE_CUSTOM || $paymentTypeId == Payment::TYPE_INSURANCE) {
                                    $monthsNumber = $get('custom_duration_months');
                                    if ($monthsNumber) {
                                        $set('subscription.end_date', Date::parse($state)->addMonths($monthsNumber));
                                    }
                                }
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_INSURANCE || $get('payment_type_id') == Payment::TYPE_CUSTOM),
                        DatePicker::make('subscription.end_date')
                            ->label(__('resources.subscription.end_date'))
                            ->native(false)
                            ->after('subscription.start_date')
                            ->disabledOn('edit')
                            ->afterStateHydrated(function ($state, callable $set, $livewire) {
                                $set(
                                    'subscription.end_date',
                                    $livewire->record->subscription->end_date ?? Date::now()->addMonths(1)
                                );
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // if custom duration months is set, update it based on the difference between start and end date
                                $paymentTypeId = $get('payment_type_id');
                                if ($paymentTypeId != Payment::TYPE_CUSTOM) {
                                    $startDate = $get('subscription.start_date');
                                    $monthsNumber = $get('custom_duration_months');
                                    if ($startDate) {
                                        $set('subscription.end_date', Date::parse($startDate)->addMonths($monthsNumber));
                                    }
                                }
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_INSURANCE || $get('payment_type_id') == Payment::TYPE_CUSTOM),
                    ]),
                Grid::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->hiddenOn('edit')
                    ->visible(fn(callable $get) => Payment::allowsDiscounts($get('payment_type_id')))
                    ->schema([
                        Select::make('discount_ids')
                            ->label(__('resources.discount.pluralModelLabel'))
                            ->multiple()
                            ->options(fn(callable $get) => Discount::availableFor(self::quoteData($get))->pluck('label', 'id'))
                            ->live()
                            ->afterStateUpdated(fn(callable $get, callable $set) => self::refreshQuote($get, $set)),
                        TextInput::make('manual_discount')
                            ->label(__('resources.discount.manual'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(callable $get, callable $set) => self::refreshQuote($get, $set)),
                        Textarea::make('discount_reason')
                            ->label(__('resources.discount.reason'))
                            ->columnSpanFull()
                            ->visible(fn(callable $get) => filled($get('discount_ids')) || (float) $get('manual_discount') > 0)
                            // A hand-typed reduction has no catalog rule behind it, so it must be justified
                            ->required(fn(callable $get) => (float) $get('manual_discount') > 0),
                    ]),
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                    ->schema([
                        TextInput::make('amount_gross')
                            ->label(__('resources.payment.amount_gross'))
                            ->default(0)
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            // Typed by hand only for types the group doesn't price; otherwise the server sets it
                            ->disabled(fn(callable $get, string $operation) => $operation === 'edit' || self::groupPrice($get) !== null)
                            ->live(onBlur: true)
                            // a recorded payment keeps its gross and reductions
                            ->afterStateUpdated(fn(callable $get, callable $set, string $operation) => $operation === 'create' ? self::refreshQuote($get, $set) : null),
                        TextInput::make('discount_total')
                            ->label(__('resources.payment.discount_total'))
                            ->default(0)
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn(callable $get, string $operation) => $operation === 'edit' ? (float) $get('amount_gross') : null)
                            ->visible(fn(callable $get) => Payment::allowsDiscounts($get('payment_type_id')))
                            // Computed from the reductions on create; only a full edit corrects it by hand
                            ->disabled(fn(string $operation) => $operation === 'create' || self::lockedOnEdit($operation))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(callable $get, callable $set) => self::refreshDue($get, $set)),
                        TextInput::make('amount_due')
                            ->label(__('resources.payment.amount_due'))
                            ->default(0)
                            ->numeric()
                            ->disabled(),
                    ]),
                Grid::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('amount_paid')
                            ->label(__('resources.payment.amount_paid'))
                            ->required()
                            ->disabled(fn(string $operation) => self::lockedOnEdit($operation))
                            ->numeric()
                            ->minValue(0)
                            // a full edit corrects what was received, it can't exceed what is owed
                            ->maxValue(fn(callable $get, string $operation) => $operation === 'edit' ? (float) $get('amount_due') : null)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(callable $get, callable $set, string $operation) => $operation === 'edit' ? self::refreshDue($get, $set) : null),
                    ]),
                // rest
                TextInput::make('payment_remaining')
                    ->label(__('resources.payment.remaining_amount'))
                    ->required()
                    ->visibleOn('edit')
                    ->disabled(true)
                    ->afterStateHydrated(function ($state, callable $set, $livewire) {
                        $set(
                            'payment_remaining',
                            $livewire->record ? $livewire->record->amount_due - $livewire->record->amount_paid : 0
                        );
                    })
                    ->numeric(),
                Select::make('status')
                    ->label(__('resources.payment.status.label'))
                    ->options(['paid' => __('resources.payment.status.paid'), 'unpaid' => __('resources.payment.status.unpaid'), 'partial' => __('resources.payment.status.partial'), 'free' => __('resources.payment.status.free')])
                    // Derived from the amounts by the Payment model
                    ->visibleOn('edit')
                    ->disabled(),
                DatePicker::make('applies_to_date')
                    ->label(__('resources.payment.applies_to_date'))
                    ->default(fn() => Date::now())
                    ->displayFormat('F Y')
                    ->disabled(fn (string $operation) => self::lockedOnEdit($operation))
                    ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_MONTHLY)
                    ->required(),
                DatePicker::make('payment_date')
                    ->label(__('resources.payment.payment_date'))
                    ->default(fn() => Date::now())
                    ->disabled(fn (string $operation) => self::lockedOnEdit($operation))
                    ->required(),
                TextEntry::make('reductions')
                    ->label(fn(?Payment $record) => $record?->isSubscriptionMonth()
                        ? __('resources.subscription.reductions')
                        : __('resources.discount.pluralModelLabel'))
                    ->visible(fn(?Payment $record) => $record?->reductions()->isNotEmpty() ?? false)
                    ->state(fn(Payment $record) => $record->reductions()
                        ->map(fn($discount) => "{$discount->label}: -" . number_format((float) $discount->amount, 2) . ' MAD'
                            . ($discount->reason ? " — {$discount->reason}" : ''))
                        ->all())
                    ->listWithLineBreaks()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label(__('resources.notes'))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Most fields are read-only once a payment is recorded, unless the user holds FullEdit:Payment.
     * Disabled fields are not submitted, so this also guards the save.
     */
    private static function lockedOnEdit(string $operation): bool
    {
        return $operation === 'edit' && ! auth()->user()?->can('fullEdit', Payment::class);
    }

    /**
     * Full edit: the amount due follows the hand-corrected reduction (EditPayment recomputes it on save).
     */
    private static function refreshDue(callable $get, callable $set): void
    {
        $due = max(0, round((float) $get('amount_gross') - (float) $get('discount_total'), 2));

        $set('amount_due', $due);
        $set('payment_remaining', round($due - (float) $get('amount_paid'), 2));
    }

    private static function groupPrice(callable $get): ?float
    {
        $groupId = $get('group_id');

        return PriceQuote::listPrice($groupId ? Group::find($groupId) : null, $get('payment_type_id'), (int) $get('custom_duration_months'));
    }

    /**
     * Preview of the price; CreatePayment recomputes it from the same fields on save.
     */
    private static function refreshQuote(callable $get, callable $set): void
    {
        $quote = PriceQuote::forPayment(self::quoteData($get));

        $set('amount_gross', $quote->gross);
        $set('discount_total', $quote->discountTotal);
        $set('amount_due', $quote->net());
    }

    /**
     * After a change that affects eligibility (type, duration, trainee...): drop the reductions that
     * no longer apply and pre-select the automatic ones. The cashier can still remove them.
     */
    private static function syncDiscounts(callable $get, callable $set): void
    {
        $available = Discount::availableFor(self::quoteData($get));
        $kept = $available->whereIn('id', $get('discount_ids') ?? []);

        $set('discount_ids', $kept->merge($available->where('is_automatic', true))
            ->map(fn(Discount $discount) => (string) $discount->id)
            ->unique()
            ->values()
            ->all());
    }

    private static function quoteData(callable $get): array
    {
        return [
            'payment_type_id' => $get('payment_type_id'),
            'group_id' => $get('group_id'),
            'trainee_id' => $get('trainee_id'),
            'custom_duration_months' => $get('custom_duration_months'),
            'payment_date' => $get('payment_date'),
            'amount_gross' => $get('amount_gross'),
            'amount_paid' => $get('amount_paid'),
            'discount_ids' => $get('discount_ids'),
            'manual_discount' => $get('manual_discount'),
        ];
    }
}
