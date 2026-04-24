<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionSettings extends Model
{
    protected $fillable = [
        'faction_id',
        'faction_name',
        'torn_api_key',
        'ffscouter_api_key',
        'auto_sync_enabled',
        'sync_settings',
        'base_domain',
        'travel_method',
        'discord_enabled',
        'discord_bot_token',
        'discord_server_id',
        'discord_channel_id',
        'war_mode_enabled',
    ];

    protected $casts = [
        'auto_sync_enabled' => 'boolean',
        'discord_enabled' => 'boolean',
        'sync_settings' => 'array',
        'war_mode_enabled' => 'boolean',
    ];

    protected $hidden = [
        'torn_api_key',
        'ffscouter_api_key',
    ];
}
