<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_FREE_LABEL = 'Free (legacy)';

    /**
     * amount_due stays the net amount owed (gross - discount_total), so every report built on it keeps working.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount_gross', 8, 2)->default(0)->after('amount_due')->comment('List price before reductions');
            $table->decimal('discount_total', 8, 2)->default(0)->after('amount_gross');
        });

        DB::table('payments')->update(['amount_gross' => DB::raw('amount_due')]);

        // Status is now derived from the amounts: a "free" payment is one fully covered by a reduction.
        DB::table('payments')
            ->where('status', 'free')
            ->where('amount_due', '>', 0)
            ->orderBy('id')
            ->each(function ($payment) {
                DB::table('applied_discounts')->insert([
                    'discountable_type' => 'App\\Models\\Payment',
                    'discountable_id' => $payment->id,
                    'label' => self::LEGACY_FREE_LABEL,
                    'type' => 'percent',
                    'value' => 100,
                    'amount' => $payment->amount_due,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('payments')->where('id', $payment->id)->update([
                    'discount_total' => $payment->amount_due,
                    'amount_due' => 0,
                ]);
            });
    }

    public function down(): void
    {
        $legacy = DB::table('applied_discounts')->where('label', self::LEGACY_FREE_LABEL);
        DB::table('payments')
            ->whereIn('id', (clone $legacy)->select('discountable_id'))
            ->update(['amount_due' => DB::raw('amount_gross')]);
        $legacy->delete();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['amount_gross', 'discount_total']);
        });
    }
};
