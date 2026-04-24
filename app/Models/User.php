<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INVITED = 'invited';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'name',
        'password',
        'torn_player_id',
        'torn_api_key',
        'is_admin',
        'status',
        'invitation_token',
        'invited_by',
        'invited_at',
        'disabled_at',
        'discord_user_id',
        'discord_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'invited_at' => 'datetime',
            'disabled_at' => 'datetime',
            'discord_verified_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->invitation_token)) {
                $user->invitation_token = Str::random(64);
            }
            if (empty($user->invited_at)) {
                $user->invited_at = now();
            }
            if (empty($user->status)) {
                $user->status = self::STATUS_INVITED;
            }
        });
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInvited(): bool
    {
        return $this->status === self::STATUS_INVITED;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'invitation_token' => null,
        ]);
    }

    public function disable(): void
    {
        $this->update([
            'status' => self::STATUS_DISABLED,
            'disabled_at' => now(),
        ]);
    }

    public function regenerateInvitationToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'invitation_token' => $token,
            'status' => self::STATUS_INVITED,
        ]);
        return $token;
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}