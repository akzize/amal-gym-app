<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * A subscription of several months is the agreement; each month it covers becomes a regular
 * monthly payment. So "is this month paid?", the one-payment-per-month rule and the monthly
 * reports all work the same whether a month was paid alone or as part of a subscription.
 */
final class MonthlySubscription
{
    /**
     * @param array $data the payment form's fields
     * @return Payment the first month, the payment the form opens after saving
     */
    public static function create(array $data, PriceQuote $quote, ?string $discountReason = null): Payment
    {
        $months = max(1, (int) ($data['custom_duration_months'] ?? 1));
        $start = CarbonImmutable::parse($data['subscription']['start_date'])->startOfDay();
        $monthKeys = collect(range(0, $months - 1))->map(fn(int $i) => $start->startOfMonth()->addMonths($i));

        self::ensureMonthsAreFree($data, $monthKeys->map->toDateString()->all());

        $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);
        if ($amountPaid > $quote->net()) {
            throw ValidationException::withMessages([
                'data.amount_paid' => __('resources.subscription.overpaid', ['amount' => number_format($quote->net(), 2)]),
            ]);
        }

        $subscription = Subscription::create([
            'trainee_id' => $data['trainee_id'],
            'group_id' => $data['group_id'],
            'payment_type_id' => Payment::TYPE_CUSTOM,
            'start_date' => $start->toDateString(),
            'end_date' => $start->addMonthsNoOverflow($months)->toDateString(),
            'duration_months' => $months,
            'status' => 'active',
        ]);
        $quote->recordOn($subscription, $discountReason, auth()->id());

        $grossShares = self::split($quote->gross, $months);
        $discountShares = self::split($quote->discountTotal, $months);
        $paidLeft = $amountPaid;

        $payments = $monthKeys->map(function (CarbonImmutable $month, int $i) use ($data, $subscription, $grossShares, $discountShares, &$paidLeft) {
            $due = round($grossShares[$i] - $discountShares[$i], 2);
            // Money received upfront settles the oldest months first
            $paid = min($paidLeft, $due);
            $paidLeft = round($paidLeft - $paid, 2);

            return Payment::create([
                'subscription_id' => $subscription->id,
                'trainee_id' => $data['trainee_id'],
                'group_id' => $data['group_id'],
                'payment_type_id' => Payment::TYPE_MONTHLY,
                'amount_gross' => $grossShares[$i],
                'discount_total' => $discountShares[$i],
                'amount_due' => $due,
                'amount_paid' => $paid,
                'applies_to_date' => $month->toDateString(),
                'payment_date' => $data['payment_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return $payments->first();
    }

    /**
     * Equal shares rounded to the cent, the last one absorbing the rounding difference.
     *
     * @return float[]
     */
    public static function split(float $total, int $parts): array
    {
        $share = floor($total / $parts * 100) / 100;
        $shares = array_fill(0, $parts, $share);
        $shares[$parts - 1] = round($total - $share * ($parts - 1), 2);

        return $shares;
    }

    /**
     * @param string[] $monthKeys
     */
    private static function ensureMonthsAreFree(array $data, array $monthKeys): void
    {
        $taken = Payment::query()
            ->where('payment_type_id', Payment::TYPE_MONTHLY)
            ->where('trainee_id', $data['trainee_id'])
            ->where('group_id', $data['group_id'])
            ->whereIn('month_key', $monthKeys)
            ->orderBy('month_key')
            ->pluck('month_key');

        if ($taken->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.custom_duration_months' => __('resources.subscription.months_taken', [
                    'months' => $taken->map(fn($month) => CarbonImmutable::parse($month)->format('Y-m'))->implode('، '),
                ]),
            ]);
        }
    }
}
