<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    /** @use HasFactory<\Database\Factories\TrainerFactory> */
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(related: User::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
