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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    protected function handleRecordCreation(array $data): Model
    {
        // We use a database transaction to ensure atomicity:
        // If the subscription is created but the payment fails, everything is rolled back.
        $payment = DB::transaction(
            function () use ($data) {

                $subscription = null;

                // --- 1. SUBSCRIPTION CREATION LOGIC ---
                if ($data['payment_type_id'] == Payment::TYPE_INSURANCE) {
                    // Check for existing subscription (assuming isSubscriptionCreated throws an exception if found)
                    $this->isSubscriptionCreated($data);

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
                } elseif ($data['payment_type_id'] == Payment::TYPE_CUSTOM) {
                    // Check for existing subscription
                    $this->isSubscriptionCreated($data);

                    $subscription = Subscription::createOrFirst([
                        'trainee_id' => $data['trainee_id'],
                        'group_id' => $data['group_id'],
                        'payment_type_id' => $data['payment_type_id'],
                        'start_date' => $data['subscription']['start_date'],
                        'end_date' => $data['subscription']['end_date'],
                        'duration_months' => $data['custom_duration_months'],
                        'status' => 'active',
                    ]);

                    // unset subscription data from payment data
                    unset($data['subscription']);
                } elseif ($data['payment_type_id'] == Payment::TYPE_ONE_SESSION) {
                    $data['amount_due'] = $data['amount_paid'];
                }

                // Update the data array for Payment creation
                if ($subscription) {
                    $data['subscription_id'] = $subscription->id;
                }

                // Unset fields used only for logic
                unset($data['subscription']);
                unset($data['custom_duration_months']);

                // --- 2. PAYMENT CREATION ---
                // Create the main Payment record within the transaction
                $payment = static::getModel()::create($data);
                return $payment;
            }
        );
        return $payment;
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
