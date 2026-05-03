<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymStatsHistory extends Model
{
    protected $table = 'gym_stats_history';
    
    protected $fillable = [
        'user_id',
        'strength',
        'defense',
        'speed',
        'dexterity',
        'strength_modifier',
        'defense_modifier',
        'speed_modifier',
        'dexterity_modifier',
        'gym_name',
        'gym_id',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}