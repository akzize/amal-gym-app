<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One payout per trainer per month; expected_amount is frozen when the payout is opened.
        Schema::create('trainer_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->date('month_key')->comment('First day of the month this payout covers');
            $table->decimal('expected_amount', 10, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'month_key']);
        });

        Schema::create('trainer_payout_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_payout_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('trainer_payment_records')) {
            return;
        }

        // Fold the old one-row-per-payment records into payouts + installments.
        DB::table('trainer_payment_records')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($record) => $record->trainer_id . '|' . Carbon::parse($record->applies_to_date)->startOfMonth()->toDateString())
            ->each(function ($records) {
                $first = $records->first();
                $expected = (float) $first->expected_amount;
                $paid = $records->sum(fn ($record) => (float) $record->amount_paid);

                $payoutId = DB::table('trainer_payouts')->insertGetId([
                    'trainer_id' => $first->trainer_id,
                    'month_key' => Carbon::parse($first->applies_to_date)->startOfMonth()->toDateString(),
                    'expected_amount' => $expected,
                    'status' => $paid <= 0 ? 'unpaid' : ($paid >= $expected ? 'paid' : 'partial'),
                    'created_at' => $first->created_at,
                    'updated_at' => $records->last()->updated_at,
                ]);

                DB::table('trainer_payout_installments')->insert($records->map(fn ($record) => [
                    'trainer_payout_id' => $payoutId,
                    'amount' => $record->amount_paid,
                    'paid_at' => $record->paid_at,
                    'notes' => $record->notes,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_payout_installments');
        Schema::dropIfExists('trainer_payouts');
    }
};
