<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionMember extends Model
{
    protected $fillable = [
        'faction_id',
        'player_id',
        'name',
        'level',
        'rank',
        'position',
        'days_in_faction',
        'status_description',
        'status_color',
        'online_status',
        'ff_score',
        'estimated_stats',
        'status_changed_at',
        'travel_started_at',
        'data',
        'last_synced_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_synced_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'travel_started_at' => 'datetime',
    ];

    public function player()
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id', 'player_id');
    }
}
