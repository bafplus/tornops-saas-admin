<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncFFStats extends Command
{
    protected $signature = 'torn:sync-ffstats {--force : Force sync even during active war}';
    protected $description = 'Sync FF stats for faction members using FF Scouter';

    public function handle(FFScouterService $ffscouter): int
    {
        $factionId = FactionSettings::value('faction_id');

        if (!$factionId) {
            return Command::FAILURE;
        }

        $log = DataRefreshLog::logStart('member_stats');

        $members = FactionMember::where('faction_id', $factionId)->get();

        if ($members->isEmpty()) {
            $log->update(['status' => 'skipped', 'completed_at' => now()]);
            $this->info('No members found.');
            return Command::SUCCESS;
        }

        $playerIds = $members->pluck('player_id')->toArray();

        $ffResults = $ffscouter->getStats($playerIds);
        $updated = 0;

        foreach ($ffResults as $ff) {
            $playerId = $ff['player_id'];
            
            FactionMember::where('player_id', $playerId)
                ->where('faction_id', $factionId)
                ->update([
                    'ff_score' => $ff['fair_fight'] ?? null,
                    'estimated_stats' => $ff['bs_estimate_human'] ?? null,
                    'ff_synced_at' => now(),
                ]);
            $updated++;
        }

        $log->markComplete($updated);
        $this->info("Updated FF stats for {$updated} members.");
        return Command::SUCCESS;
    }
}
