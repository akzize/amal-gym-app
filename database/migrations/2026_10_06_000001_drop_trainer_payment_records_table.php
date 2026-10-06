<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Replaced by trainer_payouts + trainer_payout_installments.
    public function up(): void
    {
        Schema::dropIfExists('trainer_payment_records');
    }

    public function down(): void
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

        // Restore each installment as a flat record so the old code keeps working.
        DB::table('trainer_payout_installments')
            ->join('trainer_payouts', 'trainer_payouts.id', '=', 'trainer_payout_installments.trainer_payout_id')
            ->orderBy('trainer_payout_installments.id')
            ->select('trainer_payout_installments.*', 'trainer_payouts.trainer_id', 'trainer_payouts.month_key', 'trainer_payouts.expected_amount', 'trainer_payouts.status')
            ->get()
            ->each(fn ($row) => DB::table('trainer_payment_records')->insert([
                'trainer_id' => $row->trainer_id,
                'amount_paid' => $row->amount,
                'expected_amount' => $row->expected_amount,
                'status' => $row->status,
                'applies_to_date' => $row->month_key,
                'paid_at' => substr($row->paid_at, 0, 10),
                'notes' => $row->notes,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]));
    }
};
