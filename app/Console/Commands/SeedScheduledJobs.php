<?php

namespace App\Console\Commands;

use App\Models\ScheduledJob;
use Illuminate\Console\Command;

class SeedScheduledJobs extends Command
{
    protected $signature = 'jobs:seed';
    protected $description = 'Seed scheduled jobs with clean defaults';
    protected $jobs = [
        'torn:sync-faction' => ['d' => 'Sync faction members', 'c' => '*/5 * * * *', 'w' => '*/1 * * * *', 'a' => '4 calls'],
        'torn:sync-ffstats' => ['d' => 'Sync FF stats', 'c' => '*/5 * * * *', 'w' => '*/5 * * * *', 'a' => '16 calls'],
        'torn:sync-wars' => ['d' => 'Sync ranked wars', 'c' => '*/10 * * * *', 'w' => '*/5 * * * *', 'a' => '2 calls'],
        'torn:sync-active' => ['d' => 'War updates', 'c' => '*/5 * * * *', 'w' => '*/1 * * * *', 'a' => '1 call'],
        'torn:sync-attacks' => ['d' => 'War attacks', 'c' => '*/5 * * * *', 'w' => '*/1 * * * *', 'a' => '2 calls'],
        'torn:sync-chains' => ['d' => 'War chains', 'c' => '*/5 * * * *', 'w' => '*/1 * * * *', 'a' => '1 call'],
        'torn:sync-ocs' => ['d' => 'Organised crimes', 'c' => '*/10 * * * *', 'w' => '*/5 * * * *', 'a' => '1 call'],
        'torn:sync-stocks' => ['d' => 'Sync stocks', 'c' => '0 * * * *', 'w' => '*/10 * * * *', 'a' => '1 call'],
        'torn:sync-items' => ['d' => 'Sync items', 'c' => '0 0 * * *', 'w' => '0 0 * * *', 'a' => '1 call'],
        'torn:sync-profiles' => ['d' => 'Sync player profiles', 'c' => '0 * * * *', 'w' => '0 * * * *', 'a' => '50-100 calls'],
    ];

    public function handle(): int
    {
        ScheduledJob::truncate();
        
        foreach ($this->jobs as $cmd => $j) {
            ScheduledJob::create([
                'command' => $cmd,
                'description' => $j['d'],
                'enabled' => true,
                'cron_expression' => $j['c'],
                'war_mode_only' => false,
                'war_cron' => $j['w'],
                'api_est' => $j['a'],
            ]);
            $this->info("Created: $cmd");
        }

        $this->newLine();
        $this->info('Seeded ' . count($this->jobs) . ' jobs');
        return Command::SUCCESS;
    }
}