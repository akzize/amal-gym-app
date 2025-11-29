<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Trainee;
use Dom\Text;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
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
                    ->disabledOn('edit')
                    ->live(onBlur: true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $traineeId = $get('trainee_id');
                        $trainee = Trainee::find($traineeId);
                        $groups = $trainee ? $trainee->groups : [];

                        // if the trainee has multiple groups, need to decide which group's fee to use
                        if (count($groups) <= 1) {
                            $group = $groups ? $groups->first() : null;

                            $set('amount_due', $group ? $group->monthly_fee : 0);
                            $set('show_group_select', false);
                        }

                        $groupId = $get('group_id');
                        $insuranceDurationMonths = 1;
                        $group = $groupId ? Group::find($groupId) : null;
                        $selected_type = $get('payment_type_id');
                        if ($selected_type && $selected_type == Payment::TYPE_MONTHLY) {
                            $set('amount_due', $group ? $group->monthly_fee : 0);
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSURANCE) {
                            $set('amount_due', $group ? $group->insurance_fee : 0);
                            $insuranceDurationMonths = PaymentType::find($selected_type)->duration_months;
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSCRIPTION) {
                            $set('amount_due', $group ? $group->registration_fee : 0);
                        }

                        // initial update of the subscription end date
                        if ($selected_type && ($selected_type == Payment::TYPE_INSURANCE || $selected_type == Payment::TYPE_CUSTOM)) {
                            $set('custom_duration_months', $insuranceDurationMonths);
                            $start_date = $get('subscription.start_date');
                            $set('subscription.end_date', Date::parse($start_date)->addMonths($insuranceDurationMonths));
                        }
                    })
                    ->required(),
                Select::make('trainee_id')
                    ->label(__('resources.trainee.label'))
                    ->disabledOn('edit')
                    ->relationship('trainee', 'full_name')
                    ->searchable()
                    ->live()
                    ->partiallyRenderComponentsAfterStateUpdated(['payment_type_id', 'group_id'])
                    ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                    ->preload(),
                // Group select in case the trainee has multiple groups
                Select::make('group_id')
                    ->label(__('resources.group.label'))
                    ->disabledOn('edit')

                    ->options(function (callable $get, $set) {
                        $traineeId = $get('trainee_id');
                        $trainee = Trainee::find($traineeId);
                        $groups = $trainee ? $trainee->groups->pluck('name', 'id') : [];

                        return $groups;
                    })
                    ->default(fn(callable $get) => $get('default_group_id'))
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $groupId = $get('group_id');
                        $group = Group::find($groupId);

                        $selected_type = $get('payment_type_id');
                        if ($selected_type && ($selected_type == Payment::TYPE_MONTHLY || $selected_type == Payment::TYPE_CUSTOM)) {
                            $amount_due = $group ? $group->monthly_fee : 0;
                            if ($selected_type == Payment::TYPE_CUSTOM)
                                $amount_due = $amount_due * (int)$get('custom_duration_months');
                            $set('amount_due', $amount_due);
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSURANCE) {
                            $set('amount_due', $group ? $group->insurance_fee : 0);
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSCRIPTION) {
                            $set('amount_due', $group ? $group->registration_fee : 0);
                        }
                    })
                    ->reactive()
                    // ->disabledOn('edit')
                    ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION),

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
                        // you can add any logic here if needed when custom duration changes
                        $monthsNumber = (int)$state;

                        // update the end date
                        $set('subscription.end_date', Date::parse($get('subscription.start_date'))->addMonths($monthsNumber));

                        $groupID = $get('group_id');
                        $selected_type = $get('payment_type_id');
                        if ($groupID && $selected_type == Payment::TYPE_CUSTOM) {
                            $group = Group::find($groupID);
                            $amount_due = $group ? $group->monthly_fee * $monthsNumber : 0;
                            $set('amount_due', $amount_due);
                        }
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
                    ->schema([
                        TextInput::make('amount_due')
                            ->label(__('resources.payment.amount_due'))
                            ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                            ->default(0)
                            ->required()
                            ->disabledOn('edit')
                            ->numeric(),
                        TextInput::make('amount_paid')
                            ->label(__('resources.payment.amount_paid'))
                            ->required()
                            ->disabledOn('edit')
                            ->numeric(),
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
                    ->default('unpaid')
                    ->disabledOn('edit')
                    ->required(),
                DatePicker::make('applies_to_date')
                    ->label(__('resources.payment.applies_to_date'))
                    ->default(fn() => Date::now())
                    ->displayFormat('F Y')
                    ->disabledOn('edit')
                    ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_MONTHLY)
                    ->required(),
                DatePicker::make('payment_date')
                    ->label(__('resources.payment.payment_date'))
                    ->default(fn() => Date::now())
                    ->disabledOn('edit')
                    ->required(),
                Textarea::make('notes')
                    ->label(__('resources.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
