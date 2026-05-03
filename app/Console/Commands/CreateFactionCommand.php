<?php

namespace App\Console\Commands;

use App\Models\Faction;
use Illuminate\Console\Command;

class CreateFactionCommand extends Command
{
    protected $signature = 'faction:create {slug} {master_key} {torn_faction_id}';
    protected $description = 'Create a faction instance';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $masterKey = $this->argument('master_key');
        $tornId = $this->argument('torn_faction_id');

        $faction = Faction::create([
            'slug' => $slug,
            'name' => $slug,
            'torn_faction_id' => $tornId,
            'status' => 'creating',
            'master_key' => $masterKey,
            'log' => 'Starting creation...',
        ]);

        $this->info("Created faction {$slug}, now provisioning...");

        $result = $this->call('faction:provision', [
            'slug' => $slug,
        ]);

        return $result;
    }
}