<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->decimal('insurance_fee', 8, 2)->default(0);
            $table->decimal('insurance_profit', 8, 2)->default(0);
            $table->decimal('registration_fee', 8, 2)->default(0);
            
            $table->integer('max_capacity')->default(-1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
            $table->dropColumn('max_capacity');
        });
    }
};
