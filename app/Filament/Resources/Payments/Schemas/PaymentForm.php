<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Group;
use App\Models\Payment;
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
                    ->relationship('paymentType', 'name')
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
                        $group = $groupId ? Group::find($groupId) : null;
                        $selected_type = $get('payment_type_id');
                        if ($selected_type && $selected_type == Payment::TYPE_MONTHLY) {
                            $set('amount_due', $group ? $group->monthly_fee : 0);
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSURANCE) {
                            $set('amount_due', $group ? $group->insurance_fee : 0);
                        } else if ($selected_type && $selected_type == Payment::TYPE_INSCRIPTION) {
                            $set('amount_due', $group ? $group->registration_fee : 0);
                        }
                    })
                    ->required(),
                Select::make('trainee_id')
                    ->relationship('trainee', 'full_name')
                    ->searchable()
                    ->live()
                    ->partiallyRenderComponentsAfterStateUpdated(['payment_type_id', 'group_id'])
                    ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                    ->preload(),
                // Group select in case the trainee has multiple groups
                Select::make('group_id')
                    ->label('Group')
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
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->hiddenOn('edit')
                    ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_CUSTOM)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // you can add any logic here if needed when custom duration changes
                        $monthsNumber = (int)$state;

                        // update the end date
                        $set('subscription.end_date', Date::parse($get('subscription.start_date'))->addMonths($monthsNumber));

                        $groupID = $get('group_id');
                        if ($groupID) {
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
                            ->label('Subscription Start Date')
                            ->native(false)
                            ->afterStateHydrated(function ($state, callable $set, $livewire) {
                                $set(
                                    'subscription.start_date',
                                    $livewire->record->subscription->start_date ?? Date::now()
                                );
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // if custom duration months is set, update end date based on start date + custom months
                                $paymentTypeId = $get('payment_type_id');
                                if ($paymentTypeId == Payment::TYPE_CUSTOM) {
                                    $monthsNumber = $get('custom_duration_months');
                                    if ($monthsNumber) {
                                        $set('subscription.end_date', Date::parse($state)->addMonths($monthsNumber));
                                    }
                                }
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_INSURANCE || $get('payment_type_id') == Payment::TYPE_CUSTOM),
                        DatePicker::make('subscription.end_date')
                            ->label('Subscription End Date')
                            ->native(false)
                            ->after('subscription.start_date')
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
                            ->hidden(fn(callable $get) => $get('payment_type_id') == Payment::TYPE_ONE_SESSION)
                            ->default(0)
                            ->required()
                            ->disabledOn('edit')
                            ->numeric(),
                        TextInput::make('amount_paid')
                            ->required()
                            ->numeric(),
                    ]),
                // rest
                TextInput::make('payment_remaining')
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
                    ->options(['paid' => 'Paid', 'unpaid' => 'Unpaid', 'partial' => 'Partial', 'free' => 'Free'])
                    ->default('unpaid')
                    ->required(),
                DatePicker::make('payment_date')
                    ->native(false)
                    ->default(fn() => Date::now())
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
