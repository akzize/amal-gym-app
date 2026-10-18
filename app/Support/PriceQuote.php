<?php

namespace App\Support;

use App\Models\Discount;
use App\Models\Group;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * The single place that prices a payment: list price, reductions, net amount owed.
 * The payment form uses it for the live preview and CreatePayment recomputes it on save,
 * so the amount stored never comes from the browser.
 */
final class PriceQuote
{
    /**
     * @param array<int, array{discount_id: ?int, label: string, type: string, value: float, amount: float}> $lines
     */
    private function __construct(
        public readonly float $gross,
        public readonly array $lines,
        public readonly float $discountTotal,
    ) {}

    public function net(): float
    {
        return round($this->gross - $this->discountTotal, 2);
    }

    /**
     * Price set by the group for this payment type, or null when the type has no group price
     * (yearly, one session...) and the amount is entered by hand.
     */
    public static function listPrice(?Group $group, $paymentTypeId, int $months = 1): ?float
    {
        if (! $group) {
            return null;
        }

        return match ((int) $paymentTypeId) {
            Payment::TYPE_MONTHLY => (float) $group->monthly_fee,
            Payment::TYPE_CUSTOM => (float) $group->monthly_fee * max(1, $months),
            Payment::TYPE_INSURANCE => (float) $group->insurance_fee,
            Payment::TYPE_INSCRIPTION => (float) $group->registration_fee,
            default => null,
        };
    }

    /**
     * Quote from the payment form's fields. The gross comes from the group whenever it sets a price,
     * the typed amount_gross is only used for types it doesn't price.
     */
    public static function forPayment(array $data): self
    {
        $typeId = $data['payment_type_id'] ?? null;

        // One session is priced by what the trainee hands over, with no reductions
        if ((int) $typeId === Payment::TYPE_ONE_SESSION) {
            return self::make((float) ($data['amount_paid'] ?? 0));
        }

        $group = filled($data['group_id'] ?? null) ? Group::find($data['group_id']) : null;
        $gross = self::listPrice($group, $typeId, (int) ($data['custom_duration_months'] ?? 1))
            ?? (float) ($data['amount_gross'] ?? 0);

        if (! Payment::allowsDiscounts($typeId)) {
            return self::make($gross);
        }

        return self::make(
            $gross,
            // only what the cashier selected, and only if the payment qualifies for it
            Discount::availableFor($data)->whereIn('id', $data['discount_ids'] ?? []),
            (float) ($data['manual_discount'] ?? 0),
        );
    }

    /**
     * Percentages apply to the gross price (they don't compound), fixed amounts are subtracted as is,
     * and the total reduction never exceeds the gross price.
     *
     * @param iterable<Discount> $discounts
     */
    public static function make(float $gross, iterable $discounts = [], float $manualAmount = 0, ?string $manualLabel = null): self
    {
        $gross = round(max(0, $gross), 2);
        $remaining = $gross;
        $lines = [];

        $add = function (?int $discountId, string $label, string $type, float $value, float $amount) use (&$remaining, &$lines) {
            $amount = round(min($remaining, max(0, $amount)), 2);
            if ($amount <= 0) {
                return;
            }
            $remaining -= $amount;
            $lines[] = ['discount_id' => $discountId, 'label' => $label, 'type' => $type, 'value' => $value, 'amount' => $amount];
        };

        foreach ($discounts as $discount) {
            $value = (float) $discount->value;
            $amount = $discount->type === Discount::TYPE_PERCENT ? $gross * $value / 100 : $value;
            $add($discount->id, $discount->name_ar ?: $discount->name, $discount->type, $value, $amount);
        }

        if ($manualAmount > 0) {
            $add(null, $manualLabel ?? __('resources.discount.manual'), Discount::TYPE_FIXED, $manualAmount, $manualAmount);
        }

        return new self($gross, $lines, round($gross - $remaining, 2));
    }

    /**
     * Record the reductions on the payment (or subscription) they were applied to.
     */
    public function recordOn(EloquentModel $discountable, ?string $reason = null, ?int $approvedBy = null): void
    {
        foreach ($this->lines as $line) {
            $discountable->appliedDiscounts()->create($line + ['reason' => $reason, 'approved_by' => $approvedBy]);
        }
    }
}
