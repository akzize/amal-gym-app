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
        Schema::create('group_trainees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups', 'id')->onDelete('cascade');
            $table->foreignId('trainee_id')->constrained('trainees', 'id')->onDelete('cascade');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_trainees');
    }
};
