<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = [];

    const TYPE_PERCENT = 'percent';
    const TYPE_FIXED = 'fixed';

    // A trainee registered within this many days of the payment counts as a new member
    const NEW_MEMBER_DAYS = 30;

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
            'min_months' => 'integer',
            'new_members_only' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_automatic' => 'boolean',
        ];
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    /**
     * Active discounts usable on a payment of this type (null payment_type_id = any type).
     */
    public function scopeUsableFor(Builder $query, $paymentTypeId): Builder
    {
        // A multi-month subscription is monthly fees paid together, so monthly reductions apply to it too
        $types = (int) $paymentTypeId === Payment::TYPE_CUSTOM
            ? [Payment::TYPE_CUSTOM, Payment::TYPE_MONTHLY]
            : [$paymentTypeId];

        return $query
            ->where('is_active', true)
            ->where(fn(Builder $q) => $q->whereNull('payment_type_id')->orWhereIn('payment_type_id', $types));
    }

    /**
     * Discounts a payment with these form fields qualifies for: right type, valid on the payment date,
     * long enough for its duration tier, new-member offers only for new members.
     * Of the duration tiers only the best one is kept (6 months gets the 6-month tier, not also the 3-month one).
     */
    public static function availableFor(array $data): Collection
    {
        $typeId = $data['payment_type_id'] ?? null;
        $months = (int) $typeId === Payment::TYPE_CUSTOM ? max(1, (int) ($data['custom_duration_months'] ?? 1)) : 1;
        $on = CarbonImmutable::parse($data['payment_date'] ?? now())->startOfDay();
        $trainee = filled($data['trainee_id'] ?? null) ? Trainee::find($data['trainee_id']) : null;
        $isNewMember = $trainee?->created_at && $trainee->created_at->gte($on->subDays(self::NEW_MEMBER_DAYS));

        $discounts = static::usableFor($typeId)
            ->where(fn(Builder $q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $on))
            ->where(fn(Builder $q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $on))
            ->where(fn(Builder $q) => $q->whereNull('min_months')->orWhere('min_months', '<=', $months))
            ->when(! $isNewMember, fn(Builder $q) => $q->where('new_members_only', false))
            ->orderBy('id')
            ->get();

        $bestTier = $discounts->whereNotNull('min_months')->sortByDesc('min_months')->first();

        return $discounts->filter(fn(Discount $discount) => $discount->min_months === null || $discount->is($bestTier))->values();
    }

    public function getLabelAttribute(): string
    {
        $name = $this->name_ar ?: $this->name;
        $value = $this->type === self::TYPE_PERCENT ? (float) $this->value . '%' : number_format((float) $this->value, 2) . ' MAD';

        return "{$name} (-{$value})";
    }
}
