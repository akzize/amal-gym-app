<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trainees, trainers and groups are deactivated (or closed) rather than deleted, and the database
     * refuses deletes that used to cascade into history (payments, payouts, groups).
     */
    private const RULES = [
        // table, column, references, new rule, previous rule
        ['groups', 'sport_id', 'sports', 'restrict', 'cascade'],
        ['groups', 'trainer_id', 'trainers', 'set null', 'cascade'],
        ['groups', 'association_id', 'associations', 'restrict', 'set null'],
        ['trainer_payouts', 'trainer_id', 'trainers', 'restrict', 'cascade'],
        ['payments', 'trainee_id', 'trainees', 'restrict', 'set null'],
        ['payments', 'group_id', 'groups', 'restrict', 'set null'],
        ['payments', 'payment_type_id', 'payment_types', 'restrict', 'cascade'],
        ['subscriptions', 'group_id', 'groups', 'restrict', 'set null'],
        ['subscriptions', 'payment_type_id', 'payment_types', 'restrict', 'cascade'],
    ];

    public function up(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            // Was false, so trainees added from the form were silently left out of the active ones
            $table->boolean('is_active')->default(true)->change();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->date('closed_at')->nullable()->comment('A closed group is hidden from new payments and registrations');
        });

        foreach (self::RULES as [$table, $column, $references, $rule]) {
            $this->setDeleteRule($table, $column, $references, $rule);
        }
    }

    public function down(): void
    {
        foreach (self::RULES as [$table, $column, $references, , $previous]) {
            $this->setDeleteRule($table, $column, $references, $previous);
        }

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });

        Schema::table('trainees', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->change();
        });
    }

    private function setDeleteRule(string $table, string $column, string $references, string $rule): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $rule) {
            $blueprint->dropForeign([$column]);
            $blueprint->foreign($column)->references('id')->on($references)->onDelete($rule);
        });
    }
};
