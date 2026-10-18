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

    // A trainee who stopped coming is deactivated, not deleted
    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // relations
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_trainees')->withTimestamps()->withPivot('joined_at');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Only a trainee without payment history can be deleted (e.g. created by mistake); others are deactivated.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->payments()->exists() && ! $this->subscriptions()->exists();
    }

    /**
     * Active trainees with at least one open group whose fee for $month is not settled: no paid (or free)
     * monthly payment. Multi-month subscriptions have one monthly payment per month, so they count too.
     */
    public function scopeUnpaidForMonth(Builder $query, CarbonInterface $month): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('groups', fn(Builder $groups) => $groups
                ->whereNull('groups.closed_at')
                ->whereNotExists(fn($payments) => $payments->from('payments')
                    ->whereColumn('payments.trainee_id', 'group_trainees.trainee_id')
                    ->whereColumn('payments.group_id', 'group_trainees.group_id')
                    ->whereDate('payments.month_key', $month->copy()->startOfMonth())
                    ->whereIn('payments.status', [Payment::STATUS_PAID, Payment::STATUS_FREE])));
    }
}
