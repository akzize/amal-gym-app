<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Subscription;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getForms(): array
    {
        return [
            'subscriptionConflictModal' => $this->makeForm()
                ->schema([
                    TextEntry::make('')
                        ->content('A subscription already exists for this date range.'),
                ])
                ->modalHeading('Conflict Detected')
                ->modalSubmitActionLabel('OK'),
        ];
    }

    // protected function beforeValidate()
    // {
    //     $data = $this->form->getState();

    //     $new_start = $data['subscription']['start_date'] ?? null;
    //     $new_end   = $data['subscription']['end_date'] ?? null;

    //     if (! $new_start || ! $new_end) return;

    //     $exists = Subscription::query()
    //         ->where('trainee_id', $data['trainee_id'])
    //         ->where('payment_type_id', $data['payment_type_id'])
    //         ->where(function ($q) use ($new_start, $new_end) {
    //             $q->whereDate('start_date', '<=', $new_end)
    //                 ->whereDate('end_date', '>=', $new_start);
    //         })
    //         ->exists();

    //     if ($exists) {
    //         // $this->dispatch('open-modal', id: 'subscriptionConflictModal');
    //         // $this->halt();

    //         throw ValidationException::withMessages([
    //             // attach message to a field visible on the form
    //             'subscription.start_date' => 'A subscription already exists for this trainee and payment type in the selected date range.',
    //         ]);
    //     }
    // }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // handle payment creation logic here according to payment type
        if ($data['payment_type_id'] == Payment::TYPE_INSURANCE) {
            // check if the subscription is already created for this trainee and payment type in that date
            $this->isSubscriptionCreated($data);

            // create subscription logic
            $subscription = Subscription::createOrFirst([
                'trainee_id' => $data['trainee_id'],
                'payment_type_id' => $data['payment_type_id'],
                'start_date' => $data['subscription']['start_date'],
                'end_date' => $data['subscription']['end_date'],
                'duration_months' => 6,
                'status' => 'active',
            ]);

            // unset subscription data from payment data
            unset($data['subscription']);
        } else if ($data['payment_type_id'] == Payment::TYPE_CUSTOM) {
            // check if the subscription is already created for this trainee and payment type in that date
            $this->isSubscriptionCreated($data);
            // create subscription logic
            Subscription::createOrFirst([
                'trainee_id' => $data['trainee_id'],
                'group_id' => $data['group_id'],
                'payment_type_id' => $data['payment_type_id'],
                'start_date' => $data['subscription']['start_date'],
                'end_date' => $data['subscription']['end_date'],
                'duration_months' => $data['custom_duration_months'],
                'status' => 'active',
            ]);

            // unset subscription data from payment data
            unset($data['subscription'], $data['custom_duration_months']);
        } else if ($data['payment_type_id'] == Payment::TYPE_ONE_SESSION) {
            $data['amount_due'] = $data['amount_paid'];
        }

        // create payment installment if amount paid is less than amount due
            if ($data['amount_paid'] < $data['amount_due']) {
                
                // PaymentInstallment::create([
                //     'payment_id' => $payment->id,
                //     'amount_due' => $data['amount_due'] - $data['amount_paid'],
                //     'due_date' => now()->addMonth(), // example due date
                // ]);
            }
        return parent::mutateFormDataBeforeCreate($data);
    }

    private function isSubscriptionCreated($data)
    {
        $new_start = $data['subscription']['start_date'];
        $new_end = $data['subscription']['end_date'];
        $subscriptionExists = Subscription::where('trainee_id', $data['trainee_id'])
            ->where('payment_type_id', $data['payment_type_id'])
            ->where(function ($query) use ($new_start, $new_end) {
                $query->whereDate('start_date', '<=', $new_end)
                    ->whereDate('end_date', '>=', $new_start);
            })
            ->exists();

        if ($subscriptionExists) {
            // send notification
            Notification::make()
                ->danger()
                ->title('Subscription exists')
                ->body('A subscription already exists for this trainee and payment type in the selected date range.')
                ->send();

            // throw error
            throw ValidationException::withMessages([
                // attach message to a field visible on the form
                'subscription.start_date' => 'A subscription already exists for this trainee and payment type in the selected date range.',
                'payment_type_id' => 'A subscription already exists for this trainee and payment type in the selected date range.',
            ]);
        }
    }
}
