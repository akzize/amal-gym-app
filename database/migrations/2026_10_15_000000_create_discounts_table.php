<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog of reusable reductions (sibling, staff family, scholarship...).
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 8, 2);
            // null = applies to every payment type
            $table->foreignId('payment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // A copy of the discount as it was applied, so editing or deleting the catalog never changes past payments
        Schema::create('applied_discounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('discountable');
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 8, 2);
            $table->decimal('amount', 8, 2);
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applied_discounts');
        Schema::dropIfExists('discounts');
    }
};
