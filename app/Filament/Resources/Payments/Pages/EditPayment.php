<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    #[On('refreshPaymentComponents')]
    public function refresh(): void {
        $this->refreshForm();
    }
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Amounts only arrive here on a full edit (locked fields are not submitted): the amount due
     * is recomputed from the stored gross rather than taken from the browser.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Form-only fields, not payment columns (the subscription dates are never edited here)
        unset($data['subscription'], $data['custom_duration_months'], $data['discount_ids'], $data['manual_discount'], $data['discount_reason'], $data['payment_remaining']);

        if (array_key_exists('discount_total', $data)) {
            $data['amount_due'] = max(0, round((float) $this->record->amount_gross - (float) $data['discount_total'], 2));
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // amount_paid is the installments' total, so a correction goes through the ledger
        $amountPaid = $data['amount_paid'] ?? null;
        unset($data['amount_paid']);

        try {
            return DB::transaction(function () use ($record, $data, $amountPaid) {
                $record = parent::handleRecordUpdate($record, $data);

                if ($amountPaid !== null) {
                    if ((float) $amountPaid < 0 || (float) $amountPaid > (float) $record->amount_due) {
                        throw ValidationException::withMessages([
                            'data.amount_paid' => __('validation.between.numeric', [
                                'attribute' => __('resources.payment.amount_paid'),
                                'min' => 0,
                                'max' => $record->amount_due,
                            ]),
                        ]);
                    }

                    $record->correctAmountPaid((float) $amountPaid);
                }

                return $record;
            });
        } catch (UniqueConstraintViolationException) {
            // A full edit moved this monthly payment onto a month the trainee already paid in that group
            throw ValidationException::withMessages([
                'data.applies_to_date' => __('resources.payment.duplicate_monthly_error'),
            ]);
        }
    }

    protected function afterSave(): void
    {
        // a paid-amount correction changed the installments and the status
        $this->refreshForm();
    }

    public function refreshForm(): void
    {
        $this->fillForm(); // Reload Fill form data from DB
        $this->dispatch('refresh'); // Refresh child components like widgets, stats, tables
    }
}
