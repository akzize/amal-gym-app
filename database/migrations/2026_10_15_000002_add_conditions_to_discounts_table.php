<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conditions a payment must meet for a discount to be offered, and whether it is pre-selected.
     */
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_months')->nullable()->after('payment_type_id')
                ->comment('Duration tier: only for subscriptions of at least this many months');
            $table->boolean('new_members_only')->default(false)->after('min_months');
            $table->date('valid_from')->nullable()->after('new_members_only');
            $table->date('valid_until')->nullable()->after('valid_from');
            $table->boolean('is_automatic')->default(false)->after('valid_until')
                ->comment('Pre-selected on the payment form when the payment qualifies');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['min_months', 'new_members_only', 'valid_from', 'valid_until', 'is_automatic']);
        });
    }
};
