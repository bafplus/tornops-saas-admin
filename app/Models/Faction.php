<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    protected $fillable = [
        'name', 'slug', 'torn_faction_id', 'master_key',
        'port', 'status', 'is_trial', 'monthly_cost', 'log',
        'payment', 'amount',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'amount' => 'integer',
    ];

    public function isRunning(): bool
    {
        $output = [];
        exec("docker ps -q -f name={$this->slug}", $output);
        return !empty($output);
    }
}
