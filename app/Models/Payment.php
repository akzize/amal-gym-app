<?php

namespace App\Models;

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
        static::created(function($payment) {
            // create payment installment logic here if amount paid is less than amount due
            if ($payment->amount_paid < $payment->amount_due) {
                PaymentInstallment::create([
                    'payment_id' => $payment->id,
                    'amount_paid' => $payment->amount_paid,
                    'paid_at' => now(),
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
