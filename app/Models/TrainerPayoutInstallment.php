<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerPayoutInstallment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        // Keep the parent payout's status in sync with the sum of its installments.
        static::saved(fn ($installment) => $installment->payout->refreshStatus());
        static::deleted(fn ($installment) => $installment->payout->refreshStatus());
    }

    public function payout()
    {
        return $this->belongsTo(TrainerPayout::class, 'trainer_payout_id');
    }
}
