<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * payment_installments becomes the single cash ledger for trainee payments: every dirham
     * received is an installment with the date it was paid. Payments created fully paid used
     * to have no installment, so add one for whatever their installments don't cover yet.
     */
    public function up(): void
    {
        DB::table('payments')
            ->where('amount_paid', '>', 0)
            ->orderBy('id')
            ->each(function ($payment) {
                $recorded = (float) DB::table('payment_installments')->where('payment_id', $payment->id)->sum('amount_paid');
                $missing = round((float) $payment->amount_paid - $recorded, 2);

                if ($missing <= 0) {
                    return;
                }

                DB::table('payment_installments')->insert([
                    'payment_id' => $payment->id,
                    'amount_paid' => $missing,
                    'paid_at' => $payment->payment_date ?? $payment->created_at,
                    'notes' => 'backfill',
                    'created_at' => $payment->created_at,
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('payment_installments')->where('notes', 'backfill')->delete();
    }
};
