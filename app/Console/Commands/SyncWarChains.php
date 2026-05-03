<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\RankedWar;
use App\Models\WarChain;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use Illuminate\Console\Command;

class SyncWarChains extends Command
{
    protected $signature = 'torn:sync-chains';
    protected $description = 'Sync chain data from Torn API for active wars';

    public function handle(TornApiService $tornApi): int
    {
        $factionId = FactionSettings::value('faction_id');
        if (!$factionId) {
            return Command::FAILURE;
        }

        $log = DataRefreshLog::logStart('war_chains');

        $activeWars = RankedWar::where('status', 'in progress')->get();
        if ($activeWars->isEmpty()) {
            $log->update(['status' => 'skipped', 'completed_at' => now()]);
            $this->info('No active wars - chains sync skipped.');
            return Command::SUCCESS;
        }

        $this->info("Syncing chains for {$activeWars->count()} active war(s)...");

        foreach ($activeWars as $war) {
            $this->syncWarChain($war, $factionId, $tornApi);
            $this->syncWarChain($war, $war->opponent_faction_id, $tornApi);
        }

        $log->markComplete($activeWars->count());
        return Command::SUCCESS;
    }

    private function syncWarChain(RankedWar $war, int $factionId, TornApiService $tornApi): void
    {
        $chain = $tornApi->getFactionChain($factionId);
        if (!$chain) {
            return;
        }

        $now = now();
        $currentChain = $chain['current'] ?? 0;
        $timeout = $chain['timeout'] ?? 0;
        $modifier = $chain['modifier'] ?? 1;

        $expiresAt = null;
        if ($currentChain > 0 && $timeout > 0) {
            $expiresAt = $now->addSeconds($timeout);
        }

        $maxChain = $chain['max'] ?? 0;

        $existing = WarChain::where('war_id', $war->war_id)->where('faction_id', $factionId)->first();
        if ($existing && $existing->max_chain > $maxChain) {
            $maxChain = $existing->max_chain;
        }

        WarChain::updateOrCreate(
            ['war_id' => $war->war_id, 'faction_id' => $factionId],
            [
                'current_chain' => $currentChain,
                'max_chain' => $maxChain,
                'chain_hits' => $currentChain,
                'chain_respect' => 0,
                'modifier' => $modifier,
                'last_hit_at' => ($chain['end'] ?? 0) > 0 ? $now->createFromTimestamp($chain['end']) : null,
                'expires_at' => $expiresAt,
            ]
        );
    }
}