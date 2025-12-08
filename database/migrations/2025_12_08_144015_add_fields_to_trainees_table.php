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
        Schema::table('trainees', function (Blueprint $table) {
            // last_name, is_active increment_contract_number whatsap_number
            $table->string('last_name')->after('full_arabic_name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('increment_contract_number')->nullable();
            $table->string('whatsap_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            $table->dropColumn('last_name');
            $table->dropColumn('is_active');
            $table->dropColumn('increment_contract_number');
            $table->dropColumn('whatsap_number');
        });
    }
};
