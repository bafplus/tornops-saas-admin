<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateFaction extends Command
{
    protected $signature = 'faction:create {slug} {master_key} {api_key?}';
    protected $description = 'Create a new faction instance';

    public function handle()
    {
        $slug = $this->argument('slug');
        $masterKey = $this->argument('master_key');
        $apiKey = $this->argument('api_key');

        $service = app(\App\Services\FactionService::class);
        $result = $service->createFactionInstance($slug, $masterKey, $apiKey);

        if ($result['success']) {
            $this->info("Created faction {$slug} at https://{$slug}.tornops.net on port {$result['port']}");
        } else {
            $this->error("Failed: " . $result['error']);
        }

        return $result['success'] ? 0 : 1;
    }
}