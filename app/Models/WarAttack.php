<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarAttack extends Model
{
    protected $fillable = [
        'war_id',
        'attacker_id',
        'attacker_name',
        'defender_id',
        'defender_name',
        'result',
        'stealthed',
        'fair_fight',
        'respect_gain',
        'timestamp',
        'data',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'data' => 'array',
    ];

    protected $appends = ['score_change'];

    public function war()
    {
        return $this->belongsTo(RankedWar::class, 'war_id', 'war_id');
    }

    public function getScoreChangeAttribute()
    {
        return $this->respect_gain;
    }
}
