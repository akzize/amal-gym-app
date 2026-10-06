<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInstallment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            // Always stored as a full datetime so date-range filters on it are exact
            'paid_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
