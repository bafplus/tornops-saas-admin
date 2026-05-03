<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\RankedWar;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\WarService;
use Illuminate\Console\Command;

class SyncFaction extends Command
{
    protected $signature = 'torn:sync-faction {faction_id?} {--force : Force sync even during active war}';
    protected $description = 'Sync full faction data from Torn API';

    public function handle(TornApiService $tornApi): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing full data for faction {$factionId}...");
        $log = DataRefreshLog::logStart('faction_sync');

        $this->call('torn:sync-members', ['faction_id' => $factionId, '--force' => $this->option('force')]);
        $this->call('torn:sync-ffstats', ['--force' => $this->option('force')]);
        $this->call('torn:sync-wars', ['faction_id' => $factionId]);

        // Update settings timestamp to show last sync time
        FactionSettings::where('faction_id', $factionId)->update(['updated_at' => now()]);

        $log->markComplete();
        $this->info("Full sync completed.");
        return Command::SUCCESS;
    }
}
