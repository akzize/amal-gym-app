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

    // An insurance or subscription ending within this many days is flagged as ending soon
    public const ENDING_SOON_DAYS = 30;

    public const INSURANCE_EXPIRED = 'expired';
    public const INSURANCE_EXPIRING = 'expiring';
    public const INSURANCE_VALID = 'valid';
    public const INSURANCE_NONE = 'none';

    /**
     * End date of the trainee's latest insurance: a renewed insurance replaces the one before it.
     */
    private static function latestInsuranceEnd(): Builder
    {
        return Subscription::query()
            ->selectRaw('date(max(end_date))')
            ->whereColumn('subscriptions.trainee_id', 'trainees.id')
            ->where('payment_type_id', Payment::TYPE_INSURANCE);
    }

    /**
     * Adds insurance_ends_at, which insuranceStatus() reads.
     */
    public function scopeWithInsuranceEnd(Builder $query): Builder
    {
        return $query->addSelect(['insurance_ends_at' => self::latestInsuranceEnd()]);
    }

    /**
     * Where the trainee's insurance stands today, from its end date (needs withInsuranceEnd()).
     */
    public function insuranceStatus(): string
    {
        $endsAt = $this->insurance_ends_at;

        return match (true) {
            blank($endsAt) => self::INSURANCE_NONE,
            $endsAt < today()->toDateString() => self::INSURANCE_EXPIRED,
            $endsAt <= today()->addDays(self::ENDING_SOON_DAYS)->toDateString() => self::INSURANCE_EXPIRING,
            default => self::INSURANCE_VALID,
        };
    }

    /**
     * Trainees whose insurance is in one of the given states (expired, expiring, none).
     *
     * @param  list<string>  $states
     */
    public function scopeInsuranceIn(Builder $query, array $states): Builder
    {
        $today = today()->toDateString();
        $limit = today()->addDays(self::ENDING_SOON_DAYS)->toDateString();

        return $query->where(function (Builder $query) use ($states, $today, $limit) {
            foreach ($states as $state) {
                $query->orWhere(fn(Builder $query) => match ($state) {
                    self::INSURANCE_EXPIRED => $query->where(self::latestInsuranceEnd(), '<', $today),
                    self::INSURANCE_EXPIRING => $query
                        ->where(self::latestInsuranceEnd(), '>=', $today)
                        ->where(self::latestInsuranceEnd(), '<=', $limit),
                    self::INSURANCE_NONE => $query->whereDoesntHave('subscriptions', fn(Builder $subscriptions) => $subscriptions
                        ->where('payment_type_id', Payment::TYPE_INSURANCE)),
                    default => $query->whereRaw('1 = 0'),
                });
            }
        });
    }

    /**
     * Trainees with a multi-month subscription ending soon that was not renewed for the same group.
     */
    public function scopeSubscriptionEndingSoon(Builder $query): Builder
    {
        return $query->whereHas('subscriptions', fn(Builder $subscriptions) => $subscriptions
            ->where('subscriptions.payment_type_id', Payment::TYPE_CUSTOM)
            ->whereRaw('date(subscriptions.end_date) between ? and ?', [
                today()->toDateString(),
                today()->addDays(self::ENDING_SOON_DAYS)->toDateString(),
            ])
            ->whereNotExists(fn($later) => $later->from('subscriptions as later')
                ->whereColumn('later.trainee_id', 'subscriptions.trainee_id')
                ->whereColumn('later.group_id', 'subscriptions.group_id')
                ->where('later.payment_type_id', Payment::TYPE_CUSTOM)
                ->whereColumn('later.end_date', '>', 'subscriptions.end_date')));
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
