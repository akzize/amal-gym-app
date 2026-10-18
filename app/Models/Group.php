<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['closed_at' => 'date'];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Only a group without members or payments can be deleted; others are closed.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->trainees()->exists()
            && ! Payment::where('group_id', $this->id)->exists()
            && ! Subscription::where('group_id', $this->id)->exists();
    }

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

    public function association()
    {
        return $this->belongsTo(Association::class);
    }
}
