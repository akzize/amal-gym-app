<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainee extends Model
{
    /** @use HasFactory<\Database\Factories\TraineeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function getPhotoUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : "storage/trainees/default.png";
}

    // relations
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_trainees')->withTimestamps()->withPivot('joined_at');
    }

    /**
     * Active trainees with at least one group whose fee for $month is not settled:
     * no paid (or free) monthly payment, and no active multi-month subscription covering it.
     */
    public function scopeUnpaidForMonth(Builder $query, CarbonInterface $month): Builder
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $query
            ->where('is_active', true)
            ->whereHas('groups', fn(Builder $groups) => $groups
                ->whereNotExists(fn($payments) => $payments->from('payments')
                    ->whereColumn('payments.trainee_id', 'group_trainees.trainee_id')
                    ->whereColumn('payments.group_id', 'group_trainees.group_id')
                    ->whereDate('payments.month_key', $start)
                    ->whereIn('payments.status', [Payment::STATUS_PAID, Payment::STATUS_FREE]))
                ->whereNotExists(fn($subscriptions) => $subscriptions->from('subscriptions')
                    ->whereColumn('subscriptions.trainee_id', 'group_trainees.trainee_id')
                    ->whereColumn('subscriptions.group_id', 'group_trainees.group_id')
                    ->where('subscriptions.payment_type_id', Payment::TYPE_CUSTOM)
                    ->where('subscriptions.status', 'active')
                    ->whereDate('subscriptions.start_date', '<=', $end)
                    ->whereDate('subscriptions.end_date', '>=', $start)));
    }
}
