<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('month_key')->nullable()->after('applies_to_date')
                ->comment('First day of the month covered by a monthly payment');
        });

        DB::table('payments')
            ->where('payment_type_id', 1) // Payment::TYPE_MONTHLY
            ->whereNotNull('applies_to_date')
            ->orderBy('id')
            ->each(function ($payment) {
                DB::table('payments')->where('id', $payment->id)->update([
                    'month_key' => Carbon::parse($payment->applies_to_date)->startOfMonth()->toDateString(),
                ]);
            });

        // NULL month_key (non-monthly payments) never conflicts in a unique index.
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['trainee_id', 'group_id', 'month_key'], 'payments_trainee_group_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_trainee_group_month_unique');
            $table->dropColumn('month_key');
        });
    }
};
