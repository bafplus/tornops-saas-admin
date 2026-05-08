<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    protected $fillable = [
        'name', 'slug', 'torn_faction_id', 'master_key',
        'port', 'status', 'is_trial', 'monthly_cost', 'log',
        'payment', 'amount',
        'subscription_type', 'subscription_start', 'expires_at',
        'payment_item', 'payment_amount', 'trial_used',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'amount' => 'integer',
        'trial_used' => 'boolean',
        'subscription_start' => 'datetime',
        'expires_at' => 'datetime',
        'payment_amount' => 'integer',
    ];

    public function isRunning(): bool
    {
        $output = [];
        exec("docker ps -q -f name={$this->slug}", $output);
        return !empty($output);
    }
}
