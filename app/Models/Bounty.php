<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bounty extends Model
{
    protected $fillable = [
        'target_id',
        'target_name',
        'target_level',
        'lister_id',
        'lister_name',
        'reward',
        'reason',
        'quantity',
        'is_anonymous',
        'valid_until',
        'last_synced_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'valid_until' => 'integer',
        'last_synced_at' => 'datetime',
    ];
}
