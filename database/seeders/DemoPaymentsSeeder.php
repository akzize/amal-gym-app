<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fake monthly payments for the last 6 months so the dashboard charts have something to show.
 * Not part of DatabaseSeeder; run explicitly on a dev database:
 *
 *   php artisan db:seed --class=DemoPaymentsSeeder
 */
class DemoPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoPaymentsSeeder must not run in production.');
        }

        mt_srand(42); // same data on every run

        foreach (Group::with('trainees')->get() as $group) {
            $fee = (float) ($group->monthly_fee ?: 150);

            foreach ($group->trainees->where('is_active', true) as $trainee) {
                for ($back = 5; $back >= 0; $back--) {
                    $month = CarbonImmutable::now()->subMonths($back)->startOfMonth();

                    // Skip months that already have a payment (one monthly payment per trainee/group/month)
                    if (Payment::where('trainee_id', $trainee->id)->where('group_id', $group->id)->whereDate('month_key', $month)->exists()) {
                        continue;
                    }

                    // ~75% pay in full, ~15% pay part of it, ~10% pay nothing yet
                    $roll = mt_rand(1, 100);
                    $paid = $roll <= 75 ? $fee : ($roll <= 90 ? round($fee / 2) : 0);
                    $paidOn = $month->addDays(mt_rand(0, 12));

                    $payment = Payment::create([
                        'trainee_id' => $trainee->id,
                        'group_id' => $group->id,
                        'payment_type_id' => Payment::TYPE_MONTHLY,
                        'amount_due' => $fee,
                        'amount_paid' => $paid,
                        'status' => $paid >= $fee ? Payment::STATUS_PAID : ($paid > 0 ? Payment::STATUS_PARTIAL : Payment::STATUS_UNPAID),
                        'applies_to_date' => $month->toDateString(),
                        'payment_date' => $paidOn->toDateString(),
                        'notes' => 'demo',
                    ]);

                    // Half of the partial payers settle the rest the following month
                    if ($paid > 0 && $paid < $fee && mt_rand(0, 1) && $back > 0) {
                        PaymentInstallment::create(['payment_id' => $payment->id, 'amount_paid' => $fee - $paid, 'paid_at' => $paidOn->addMonth()]);
                        $payment->update(['amount_paid' => $fee, 'status' => Payment::STATUS_PAID]);
                    }
                }
            }
        }
    }
}
