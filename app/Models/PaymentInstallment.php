<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInstallment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            // Always stored as a full datetime so date-range filters on it are exact
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        // Keep the payment's amount_paid (and so its status) in step with its installments
        static::saved(fn(PaymentInstallment $installment) => $installment->payment?->syncAmountPaid());
        static::deleted(fn(PaymentInstallment $installment) => $installment->payment?->syncAmountPaid());
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
