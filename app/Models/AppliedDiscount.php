<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppliedDiscount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function discountable()
    {
        return $this->morphTo();
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
