<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankedWar extends Model
{
    protected $fillable = [
        'war_id',
        'faction_id',
        'opponent_faction_id',
        'opponent_faction_name',
        'status',
        'start_date',
        'end_date',
        'score_ours',
        'score_them',
        'data',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'data' => 'array',
    ];

    public function attacks()
    {
        return $this->hasMany(WarAttack::class, 'war_id', 'war_id')->orderByDesc('timestamp');
    }

    public function members()
    {
        return $this->hasMany(WarMember::class, 'war_id', 'war_id');
    }
}
