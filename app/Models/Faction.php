<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'torn_faction_id',
        'status',
        'is_trial',
        'monthly_cost',
        'payment_item_id',
        'payment_item_last_checked',
        'container_name',
        'db_path',
        'master_key',
        'last_login_at',
        'created_at',
        'expires_at',
        'port',
        'log',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'monthly_cost' => 'integer',
        'payment_item_last_checked' => 'datetime',
        'last_login_at' => 'datetime',
        'expires_at' => 'datetime',
        'port' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';
}