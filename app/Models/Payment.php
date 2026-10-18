<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Payment extends Model
{
    protected $guarded = [];

    // payment status
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_FREE = 'free';

    // payment types: ids of the payment_types rows, in PaymentTypeSeeder order
    const TYPE_MONTHLY = 1; #'monthly';
    const TYPE_YEARLY = 2; #'yearly';
    const TYPE_INSCRIPTION = 3; #'inscription';
    const TYPE_ONE_SESSION = 4; #'daily session';
    const TYPE_SPORT_PASSPORT = 'sport-passport';
    const TYPE_INSURANCE = 5; #'insurance';
    const TYPE_CUSTOM = 6; #'custom: a subscription of several months';

    protected static function booted() {
        static::saving(function ($payment) {
            $isMonthly = $payment->payment_type_id == self::TYPE_MONTHLY && $payment->applies_to_date;
            $payment->month_key = $isMonthly
                ? Carbon::parse($payment->applies_to_date)->startOfMonth()->toDateString()
                : null;

            $payment->status = self::statusFor((float) $payment->amount_due, (float) $payment->amount_paid);
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

    /**
     * Status follows the amounts: nothing owed after reductions is free, otherwise it depends on what was received.
     */
    public static function statusFor(float $amountDue, float $amountPaid): string
    {
        return match (true) {
            $amountDue <= 0 => self::STATUS_FREE,
            $amountPaid >= $amountDue => self::STATUS_PAID,
            $amountPaid > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_UNPAID,
        };
    }

    /**
     * Reductions (catalog, manual, or a corrected total) only apply to monthly fees and multi-month subscriptions.
     */
    public static function allowsDiscounts($paymentTypeId): bool
    {
        return in_array((int) $paymentTypeId, [self::TYPE_MONTHLY, self::TYPE_CUSTOM], true);
    }

    /**
     * Correct what was recorded as received. The difference is applied to the latest installments
     * so the cash ledger still adds up to amount_paid; new cash is added as an installment instead.
     */
    public function correctAmountPaid(float $amount): void
    {
        $difference = round($amount - (float) $this->installments()->sum('amount_paid'), 2);
        $installments = $this->installments()->latest('paid_at')->latest('id')->get();

        if ($difference > 0) {
            $latest = $installments->first();
            $latest
                ? $latest->update(['amount_paid' => round((float) $latest->amount_paid + $difference, 2)])
                : $this->installments()->create(['amount_paid' => $difference, 'paid_at' => $this->payment_date ?? now()]);
        }

        // Take a decrease off the most recent installments first
        $toRemove = -$difference;
        foreach ($installments as $installment) {
            if ($toRemove <= 0) {
                break;
            }

            if ((float) $installment->amount_paid <= $toRemove) {
                $toRemove = round($toRemove - (float) $installment->amount_paid, 2);
                $installment->delete();
            } else {
                $installment->update(['amount_paid' => round((float) $installment->amount_paid - $toRemove, 2)]);
                $toRemove = 0;
            }
        }

        $this->syncAmountPaid();
    }

    /**
     * amount_paid is the sum of the installments (the cash ledger).
     */
    public function syncAmountPaid(): void
    {
        $this->update(['amount_paid' => round((float) $this->installments()->sum('amount_paid'), 2)]);
    }

    /**
     * One of the monthly payments a multi-month subscription is split into.
     */
    public function isSubscriptionMonth(): bool
    {
        return $this->subscription_id && $this->subscription?->payment_type_id == self::TYPE_CUSTOM;
    }

    /**
     * What a receipt for this payment covers: every month of its subscription, or just itself.
     *
     * @return Collection<int, Payment>
     */
    public function coveredPayments(): Collection
    {
        return $this->isSubscriptionMonth() ? $this->subscription->payments : collect([$this]);
    }

    /**
     * Reductions given on this payment, or on the whole subscription it is a month of.
     *
     * @return Collection<int, AppliedDiscount>
     */
    public function reductions(): Collection
    {
        return $this->isSubscriptionMonth() ? $this->subscription->appliedDiscounts : $this->appliedDiscounts;
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

    public function appliedDiscounts()
    {
        return $this->morphMany(AppliedDiscount::class, 'discountable');
    }
}
