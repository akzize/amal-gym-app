<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $guarded = [];

    // relations
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function trainees()
    {
        return $this->belongsToMany(Trainee::class, 'group_trainees')->withTimestamps()->withPivot('joined_at');
    }
}
