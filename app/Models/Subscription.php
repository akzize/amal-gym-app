<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    /**
     * A multi-month subscription has one monthly payment per month it covers.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('month_key');
    }

    public function appliedDiscounts()
    {
        return $this->morphMany(AppliedDiscount::class, 'discountable');
    }
}
