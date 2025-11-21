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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_due', 8, 2);
            $table->decimal('amount_paid', 8, 2);
            $table->enum('status', ['paid', 'unpaid', 'partial', 'free'])->default('unpaid');
            $table->dateTime('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
