<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainee extends Model
{
    /** @use HasFactory<\Database\Factories\TraineeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function getPhotoUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : "storage/trainees/default.png";
}

}
