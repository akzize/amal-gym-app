<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    // payment status
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_FREE = 'free';

    // payment types
    const TYPE_MONTHLY = 1; #'monthly';
    const TYPE_YEARLY = 2; #'yearly';
    const TYPE_INSURANCE = 3; #'insurance';
    const TYPE_ONE_SESSION = 4; #'one-session';
    const TYPE_SPORT_PASSPORT = 'sport-passport';
    const TYPE_INSCRIPTION = 5; #'inscription';
    const TYPE_CUSTOM = 6; #'inscription';

    protected static function booted() {
        static::saving(function ($payment) {
            $isMonthly = $payment->payment_type_id == self::TYPE_MONTHLY && $payment->applies_to_date;
            $payment->month_key = $isMonthly
                ? Carbon::parse($payment->applies_to_date)->startOfMonth()->toDateString()
                : null;
        });

        static::created(function($payment) {
            // Installments are the cash ledger (what was received, and when): record the
            // amount paid at creation, whether it covers the full amount due or only part of it.
            if ($payment->amount_paid > 0) {
                PaymentInstallment::create([
                    'payment_id' => $payment->id,
                    'amount_paid' => $payment->amount_paid,
                    'paid_at' => $payment->payment_date ?? now(),
                ]);
            }
        });
    }
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function installments()
    {
        return $this->hasMany(PaymentInstallment::class);
    }
}
