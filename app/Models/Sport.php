<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    /** @use HasFactory<\Database\Factories\SportFactory> */
    use HasFactory;

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->groups()->exists();
    }
}
