<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // SoftDeletes: a deleted user can no longer log in (the auth query skips trashed rows),
    // while their trainer and payout history stay intact.
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function trainer()
    {
        return $this->hasOne(Trainer::class);
    }

    /**
     * Checked on every panel request: a deactivated trainer is locked out, even mid-session.
     * (Without this contract Filament only lets users in when APP_ENV=local.)
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->trainer?->isActive() ?? true;
    }

    /**
     * Arabic name when set, otherwise the latin name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name;
    }
}
