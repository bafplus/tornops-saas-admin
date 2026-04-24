<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarChain extends Model
{
    protected $fillable = [
        'war_id',
        'faction_id',
        'current_chain',
        'max_chain',
        'chain_hits',
        'chain_respect',
        'last_hit_at',
        'expires_at',
    ];

    protected $casts = [
        'current_chain' => 'integer',
        'max_chain' => 'integer',
        'chain_hits' => 'integer',
        'chain_respect' => 'decimal:2',
        'last_hit_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function war()
    {
        return $this->belongsTo(RankedWar::class, 'war_id', 'war_id');
    }
}