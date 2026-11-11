<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per list of insured trainees sent to the insurer
        Schema::create('insurance_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            // Set once the insurance payment is part of an export, so it is never sent twice
            $table->foreignId('insurance_export_id')->nullable()->after('subscription_id')
                ->constrained('insurance_exports')->restrictOnDelete();
        });

        Schema::table('trainees', function (Blueprint $table) {
            $table->string('cin')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            $table->dropColumn('cin');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurance_export_id');
        });

        Schema::dropIfExists('insurance_exports');
    }
};
