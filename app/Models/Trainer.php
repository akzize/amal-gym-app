<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Trainer extends Model
{
    /** @use HasFactory<\Database\Factories\TrainerFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(related: User::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function trainees()
    {
        // return $this->hasManyThrough(Trainee::class, Group::class, 'trainer_id', 'id', 'id', 'id');
        return $this->hasManyThrough(Trainee::class, GroupTrainee::class, 'group_id', 'id', 'id', 'trainee_id');
    }
    /**
     * Arabic name when set, otherwise the latin (French) name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name;
    }

    public function payouts()
    {
        return $this->hasMany(TrainerPayout::class);
    }

    /**
     * The payout for the given month, or null if nothing has been paid for it yet.
     */
    public function payoutFor($month): ?TrainerPayout
    {
        return $this->payouts()
            ->whereDate('month_key', Carbon::parse($month)->startOfMonth())
            ->withSum('installments', 'amount')
            ->first();
    }

    /**
     * Pay (part of) a month. The first installment opens the payout and freezes
     * the expected amount, so later installments are measured against the same total.
     */
    public function recordPayoutInstallment($month, float $amount, ?string $notes = null): TrainerPayoutInstallment
    {
        return DB::transaction(function () use ($month, $amount, $notes) {
            // whereDate rather than firstOrCreate: the date cast may store a time part
            $payout = $this->payouts()->whereDate('month_key', Carbon::parse($month)->startOfMonth())->lockForUpdate()->first()
                ?? $this->payouts()->create([
                    'month_key' => $month,
                    'expected_amount' => $this->calculateMonthlyPayout()['total_fees_this_month'],
                ]);

            return $payout->installments()->create([
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Calculate the monthly salary for the current month based on salary_type.
     * This is a simplified example.
     */
    public function calculateMonthlyPayout()
    {
        // Get related data needed for calculation, e.g., total group fees this month, etc.
        // loadMissing lets callers eager-load groups.trainees and reuse it across calls.
        $groups = $this->loadMissing('groups.trainees')->groups->map(function ($group) {
            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'monthly_amount' => $group->monthly_fee,
                'trainees_count' => $group->trainees->count(),
                'trainer_payment' => $group->monthly_fee * $group->trainees->count(),
            ];
        });

        $total_fees_this_month = $groups->sum('trainer_payment');
        $trainees_count = $groups->sum('trainees_count');
        $groups_count = $groups->count();
        // dd($total_fees_this_month);
        return [
            'total_fees_this_month' => match ($this->salary_type) {
                'fixed' => (float) $this->salary_amount ?? 0,
                'percentage' => (($this->salary_amount ?? 0) / 100) * $total_fees_this_month,
                default => 0.0,
            },

            'trainees_count' => $trainees_count,
            'groups_count' => $groups_count,
        ];
    }

    /**
     * Summarize the trainer's payout obligation and recorded payments for a month.
     * The first recorded expected amount is retained as the month's due snapshot.
     */
    public function monthlyPayoutSummary(Carbon|string|null $period = null): array
    {
        $month = Carbon::parse($period ?? now())->startOfMonth();
        $start = $month->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $periodPayments = $this->payments()
            ->whereBetween('applies_to_date', [$start, $end]);

        $currentExpected = (float) $this->calculateMonthlyPayout()['total_fees_this_month'];
        $recordedExpected = (clone $periodPayments)->orderBy('id')->value('expected_amount');
        $expected = (float) ($recordedExpected ?? $currentExpected);
        $paid = (float) (clone $periodPayments)->sum('amount_paid');
        $remaining = max(0, $expected - $paid);

        return [
            'expected_amount' => $expected,
            'amount_paid' => $paid,
            'remaining_amount' => $remaining,
            'status' => $paid >= $expected
                ? 'paid'
                : ($paid > 0 ? 'partial' : 'unpaid'),
        ];
    }
}
