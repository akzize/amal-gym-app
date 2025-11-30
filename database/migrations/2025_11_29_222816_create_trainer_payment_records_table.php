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
        Schema::create('trainer_payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 8, 2);
            $table->decimal('expected_amount', 8, 2);
            $table->enum('status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->date('applies_to_date');
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_payment_records');
    }
};
