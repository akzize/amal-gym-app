<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TrainerPayout extends Model
{
    protected $guarded = [];

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'month_key' => 'date',
            'expected_amount' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($payout) {
            $payout->month_key = Carbon::parse($payout->month_key)->startOfMonth()->toDateString();
        });
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function installments()
    {
        return $this->hasMany(TrainerPayoutInstallment::class);
    }

    public function paidAmount(): float
    {
        // Prefer a withSum() eager load when present to avoid a query per row.
        return (float) ($this->installments_sum_amount ?? $this->installments()->sum('amount'));
    }

    public function remainingAmount(): float
    {
        // Round so float noise never makes the exact remaining amount fail maxValue()
        return max(0, round((float) $this->expected_amount - $this->paidAmount(), 2));
    }

    public function refreshStatus(): void
    {
        $paid = (float) $this->installments()->sum('amount');

        $this->update([
            'status' => match (true) {
                $paid <= 0 => self::STATUS_UNPAID,
                $paid >= (float) $this->expected_amount => self::STATUS_PAID,
                default => self::STATUS_PARTIAL,
            },
        ]);
    }
}
