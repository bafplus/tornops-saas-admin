<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerProfile extends Model
{
    protected $fillable = [
        'player_id',
        'name',
        'level',
        'rank',
        'faction_name',
        'faction_id',
        'battlestats',
        'personalstats',
        'data',
        'last_synced_at',
    ];

    protected $casts = [
        'battlestats' => 'array',
        'personalstats' => 'array',
        'data' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
