<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    /** @use HasFactory<\Database\Factories\TrainerFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(related: User::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function trainees()
    {
        // return $this->hasManyThrough(Trainee::class, Group::class, 'trainer_id', 'id', 'id', 'id');
        return $this->hasManyThrough(Trainee::class, GroupTrainee::class, 'group_id', 'id', 'id', 'trainee_id');
    }
    public function payments()
    {
        return $this->hasMany(TrainerPaymentRecord::class);
    }

    /**
     * Calculate the monthly salary for the current month based on salary_type.
     * This is a simplified example.
     */
    public function calculateMonthlyPayout()
    {
        // Get related data needed for calculation, e.g., total group fees this month, etc.
        $groups = $this->groups()->get()->map(function ($group) {
            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'monthly_amount' => $group->monthly_fee,
                'trainees_count' => $group->trainees->count(),
                'trainer_payment' => $group->monthly_fee * $group->trainees->count(),
            ];
        });

        $total_fees_this_month = $groups->sum('trainer_payment');
        $trainees_count = $groups->sum('trainees_count');
        $groups_count = $groups->count();
        // dd($total_fees_this_month);
        return [
            'total_fees_this_month' => match ($this->salary_type) {
                'fixed' => (float) $this->salary_amount ?? 0,
                'percentage' => (($this->salary_amount ?? 0) / 100) * $total_fees_this_month,
                default => 0.0,
            },

            'trainees_count' => $trainees_count,
            'groups_count' => $groups_count,
        ];
    }
}
