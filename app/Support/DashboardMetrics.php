<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Subscription;
use App\Models\Trainee;
use App\Models\TrainerPayoutInstallment;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every dashboard figure for a date range, computed in one place so the KPI cards,
 * the charts and the payments table always agree.
 *
 * - Money collected is cash-basis: payment installments by the date they were paid
 *   (every payment's money lives in payment_installments).
 * - Money outstanding belongs to the period a payment is for: its applies_to_date,
 *   falling back to payment_date, then created_at.
 */
class DashboardMetrics
{
    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        $this->from = $from->startOfDay();
        $this->to = $to->endOfDay();
    }

    /**
     * Build from the dashboard filter form; defaults to the last 6 months, this month included.
     */
    public static function fromFilters(?array $filters): self
    {
        $from = filled($filters['from'] ?? null) ? CarbonImmutable::parse($filters['from']) : static::defaultFrom();
        $to = filled($filters['to'] ?? null) ? CarbonImmutable::parse($filters['to']) : static::defaultTo();

        return $from->greaterThan($to) ? new self($to, $from) : new self($from, $to);
    }

    public static function defaultFrom(): CarbonImmutable
    {
        return CarbonImmutable::now()->subMonths(5)->startOfMonth();
    }

    public static function defaultTo(): CarbonImmutable
    {
        return CarbonImmutable::now()->endOfMonth();
    }

    /**
     * The same-length range right before this one, for "vs previous period".
     */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;

        return new self($this->from->subDays($days), $this->from->subDay());
    }

    /**
     * @return list<CarbonImmutable> first day of each month the range touches
     */
    public function months(): array
    {
        return collect(CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth()))
            ->map(fn($month) => CarbonImmutable::parse($month))
            ->values()
            ->all();
    }

    // ---- Money in -------------------------------------------------------------------

    /**
     * The cash received in the range. The dashboard payments table lists exactly these rows.
     */
    public function collectedQuery(): Builder
    {
        return PaymentInstallment::query()
            ->where('payment_installments.amount_paid', '>', 0)
            ->whereBetween('payment_installments.paid_at', [$this->from, $this->to]);
    }

    public function collected(): float
    {
        return round((float) $this->collectedQuery()->sum('amount_paid'), 2);
    }

    /**
     * @return array<string, float> 'Y-m' => amount collected that month
     */
    public function collectedByMonth(): array
    {
        $totals = $this->emptyMonths();

        foreach ($this->collectedQuery()->get(['amount_paid', 'paid_at']) as $installment) {
            $totals[CarbonImmutable::parse($installment->paid_at)->format('Y-m')] += (float) $installment->amount_paid;
        }

        return array_map(fn(float $amount): float => round($amount, 2), $totals);
    }

    /**
     * @return array<string, array<string, float>> payment type label => ['Y-m' => amount]
     */
    public function collectedByTypeAndMonth(): array
    {
        $rows = $this->collectedQuery()
            ->join('payments', 'payments.id', '=', 'payment_installments.payment_id')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'payments.payment_type_id')
            ->get(['payment_installments.amount_paid', 'payment_installments.paid_at', 'payment_types.name', 'payment_types.name_ar']);

        $byType = [];
        foreach ($rows as $row) {
            $type = $row->name_ar ?: ($row->name ?: '—');
            $byType[$type] ??= $this->emptyMonths();
            $byType[$type][CarbonImmutable::parse($row->paid_at)->format('Y-m')] += (float) $row->amount_paid;
        }

        return array_map(fn(array $months): array => array_map(fn(float $amount): float => round($amount, 2), $months), $byType);
    }

    // ---- Money owed -----------------------------------------------------------------

    /**
     * Payments whose period (what they pay for) falls in the range.
     */
    public function duePaymentsQuery(): Builder
    {
        return Payment::query()
            ->where('status', '!=', Payment::STATUS_FREE)
            ->whereBetween(DB::raw('DATE(COALESCE(applies_to_date, payment_date, created_at))'), [
                $this->from->toDateString(),
                $this->to->toDateString(),
            ]);
    }

    public function outstanding(): float
    {
        return round(array_sum($this->outstandingByMonth()), 2);
    }

    /**
     * @return array<string, float> 'Y-m' => amount still owed for payments of that month
     */
    public function outstandingByMonth(): array
    {
        $totals = $this->emptyMonths();

        foreach ($this->duePayments() as $payment) {
            $totals[$payment->period_month] += max(0, (float) $payment->amount_due - (float) $payment->amount_paid);
        }

        return array_map(fn(float $amount): float => round($amount, 2), $totals);
    }

    /**
     * Share of monthly fees due in the range that has been paid (null when nothing was due).
     */
    public function collectionRate(): ?float
    {
        $monthly = $this->duePayments()->where('payment_type_id', Payment::TYPE_MONTHLY);
        $due = (float) $monthly->sum('amount_due');

        return $due > 0 ? round(100 * (float) $monthly->sum('amount_paid') / $due, 1) : null;
    }

    // ---- Trainers -------------------------------------------------------------------

    /**
     * Cash paid out to trainers in the range (by installment date).
     */
    public function trainerPayoutsPaid(): float
    {
        return round((float) TrainerPayoutInstallment::query()
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->sum('amount'), 2);
    }

    public function net(): float
    {
        return round($this->collected() - $this->trainerPayoutsPaid(), 2);
    }

    // ---- Members --------------------------------------------------------------------

    public function activeTrainees(): int
    {
        return Trainee::query()->where('is_active', true)->count();
    }

    public function newTrainees(): int
    {
        return Trainee::query()->whereBetween('created_at', [$this->from, $this->to])->count();
    }

    /**
     * Active trainees with at least one group whose fee for the month isn't settled (now, not the range).
     */
    public static function traineesWithUnpaidMonthlyFee(?CarbonImmutable $month = null): int
    {
        return Trainee::query()->unpaidForMonth($month ?? CarbonImmutable::now())->count();
    }

    /**
     * Active subscriptions/insurances ending within the next $days days (from today, not the range).
     */
    public static function expiringSubscriptions(int $days = 30): int
    {
        return Subscription::query()
            ->where('status', 'active')
            ->whereBetween('end_date', [CarbonImmutable::today()->toDateString(), CarbonImmutable::today()->addDays($days)->toDateString()])
            ->count();
    }

    // ---- Helpers --------------------------------------------------------------------

    public static function money(float $amount): string
    {
        return number_format($amount, 2) . ' MAD';
    }

    /**
     * @return array<string, float>
     */
    private function emptyMonths(): array
    {
        return collect($this->months())->mapWithKeys(fn(CarbonImmutable $month) => [$month->format('Y-m') => 0.0])->all();
    }

    /**
     * @return Collection<int, Payment> with a period_month ('Y-m') attribute
     */
    private function duePayments(): Collection
    {
        return once(fn() => $this->duePaymentsQuery()
            ->get(['id', 'payment_type_id', 'amount_due', 'amount_paid', 'applies_to_date', 'payment_date', 'created_at'])
            ->each(fn(Payment $payment) => $payment->period_month = CarbonImmutable::parse(
                $payment->applies_to_date ?? $payment->payment_date ?? $payment->created_at
            )->format('Y-m')));
    }
}
