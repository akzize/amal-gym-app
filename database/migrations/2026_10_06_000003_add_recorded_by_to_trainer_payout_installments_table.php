<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_payout_installments', function (Blueprint $table) {
            // The admin user who recorded the payment (shown as الكاشير on the receipt)
            $table->foreignId('recorded_by')->nullable()->after('notes')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_payout_installments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
    }
};
