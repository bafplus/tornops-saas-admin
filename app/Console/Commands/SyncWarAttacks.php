<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\RankedWar;
use App\Models\WarAttack;
use App\Models\WarMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use Illuminate\Console\Command;

class SyncWarAttacks extends Command
{
    protected $signature = 'torn:sync-attacks {--force : Run for recent wars (last 30 days)}';
    protected $description = 'Sync attacks for ranked wars';

    public function handle(TornApiService $tornApi): int
    {
        $settings = FactionSettings::first();
        
        if (!$settings || !$settings->torn_api_key) {
            return Command::FAILURE;
        }

        $log = DataRefreshLog::logStart('war_attacks');

        $activeWars = RankedWar::where('status', 'in progress')->get();

        if ($activeWars->isEmpty() && !$this->option('force')) {
            $log->update(['status' => 'skipped', 'completed_at' => now()]);
            $this->info('No active wars. Use --force to sync recent wars.');
            return Command::SUCCESS;
        }

        // If force flag, get recent wars (last 30 days)
        if ($activeWars->isEmpty() && $this->option('force')) {
            $activeWars = RankedWar::where('start_date', '>', now()->subDays(30))->get();
        }

        if ($activeWars->isEmpty()) {
            $log->update(['status' => 'skipped', 'completed_at' => now()]);
            return Command::SUCCESS;
        }

        $this->info("Syncing attacks for {$activeWars->count()} war(s)...");

        $totalSaved = 0;
        foreach ($activeWars as $war) {
            $savedCount = $this->syncWarAttacks($war, $settings, $tornApi);
            $totalSaved += $savedCount;
        }

        $log->markComplete($totalSaved);
        return Command::SUCCESS;
    }

    private function syncWarAttacks(RankedWar $war, $settings, TornApiService $tornApi): int
    {
        $factionId = $settings->faction_id;
        $warStartTimestamp = $war->start_date?->timestamp ?? 0;
        $now = time();

        // Get last attack timestamp from DB to only fetch new attacks
        $lastAttack = WarAttack::where('war_id', $war->war_id)
            ->orderBy('timestamp_started', 'desc')
            ->first();
        
        $fromTimestamp = $lastAttack ? $lastAttack->timestamp_started : ($now - 14400); // Default: last 4 hours if first run
        
        // Cap at war start time
        if ($fromTimestamp < $warStartTimestamp) {
            $fromTimestamp = $warStartTimestamp;
        }

        $warFactionIds = WarMember::where('war_id', $war->war_id)
            ->distinct()
            ->pluck('faction_id')
            ->toArray();

        $warMembers = WarMember::where('war_id', $war->war_id)->get()->keyBy('player_id');
        $savedCount = 0;

        $allAttacks = [];
        $chunkSize = 1 * 3600; // 1 hour - small to avoid hitting 100 limit
        $from = $fromTimestamp;

        while ($from < $now) {
            $to = min($from + $chunkSize, $now);
            $data = $tornApi->getFactionAttacks($factionId, $settings->torn_api_key, $from, $to);

            if ($data && isset($data['attacks']) && !empty($data['attacks'])) {
                $count = count($data['attacks']);
                
                // If we hit 100, split chunk in half recursively
                if ($count >= 100 && $chunkSize > 900) {
                    $halfSize = (int)($chunkSize / 2);
                    $halfFrom = $from;
                    while ($halfFrom < $to) {
                        $halfTo = min($halfFrom + $halfSize, $to);
                        $halfData = $tornApi->getFactionAttacks($factionId, $settings->torn_api_key, $halfFrom, $halfTo);
                        if ($halfData && isset($halfData['attacks'])) {
                            $halfCount = count($halfData['attacks']);
                            if ($halfCount >= 100 && $halfSize > 900) {
                                // Recursively split
                                $quarterSize = (int)($halfSize / 2);
                                $quarterFrom = $halfFrom;
                                while ($quarterFrom < $halfTo) {
                                    $quarterTo = min($quarterFrom + $quarterSize, $halfTo);
                                    $quarterData = $tornApi->getFactionAttacks($factionId, $settings->torn_api_key, $quarterFrom, $quarterTo);
                                    if ($quarterData && isset($quarterData['attacks'])) {
                                        foreach ($quarterData['attacks'] as $attack) {
                                            $allAttacks[] = $attack;
                                        }
                                    }
                                    $quarterFrom = $quarterTo;
                                }
                            } else {
                                foreach ($halfData['attacks'] as $attack) {
                                    $allAttacks[] = $attack;
                                }
                            }
                        }
                        $halfFrom = $halfTo;
                    }
                } else {
                    foreach ($data['attacks'] as $attack) {
                        $allAttacks[] = $attack;
                    }
                }
            }

            $from = $to;
        }

        // Sort by timestamp descending (newest first)
        usort($allAttacks, function($a, $b) {
            $tsA = $a['timestamp_started'] ?? 0;
            $tsB = $b['timestamp_started'] ?? 0;
            return $tsB <=> $tsA;
        });

        foreach ($allAttacks as $attack) {
            $attackerId = $attack['attacker_id'] ?? null;
            $defenderId = $attack['defender_id'] ?? null;
            $attackerFactionId = $attack['attacker_faction'] ?? null;
            $defenderFactionId = $attack['defender_faction'] ?? null;
            $attackTimestamp = $attack['timestamp_started'] ?? $attack['timestamp'] ?? 0;
            $isApiStealthed = !empty($attack['stealthed']);

            // Check faction involvement
            $isWarAttack = in_array($attackerFactionId, $warFactionIds) || in_array($defenderFactionId, $warFactionIds);
            
            // If attacker ID is missing, it's a stealthed attack
            $isMissingAttacker = empty($attackerId);
            
            // Skip if not a war attack and not stealthed
            if (!$isWarAttack && !$isApiStealthed && !$isMissingAttacker) {
                continue;
            }

            $fairFight = $attack['modifiers']['fair_fight'] ?? $attack['fair_fight'] ?? 1.0;
            $isFairFight = $fairFight > 1.0;

            // Handle stealthed attacks (attacker identity hidden)
            if ($isMissingAttacker) {
                $attackerName = 'Stealthed';
                $attackerId = 0;
                $respectGain = 0; // Can't attribute points
            } else {
                $attackerName = $warMembers->get($attackerId)?->name 
                    ?? $attack['attacker_name'] 
                    ?? 'Player ' . $attackerId;
                $respectGain = $attack['respect_gain'] ?? 0;
            }
            
            $defenderName = null;
            if (!empty($defenderId)) {
                $defenderName = $warMembers->get($defenderId)?->name 
                    ?? $attack['defender_name'] 
                    ?? 'Player ' . $defenderId;
            }

            // Generate unique key for stealthed attacks
            $uniqueKey = [
                'war_id' => $war->war_id,
                'timestamp' => date('Y-m-d H:i:s', $attackTimestamp),
                'attacker_id' => $attackerId,
                'defender_id' => $defenderId ?? 0,
            ];

            WarAttack::updateOrCreate(
                $uniqueKey,
                [
                    'attacker_name' => $attackerName,
                    'defender_name' => $defenderName,
                    'result' => $attack['result'] ?? null,
                    'stealthed' => $isMissingAttacker || $isApiStealthed,
                    'fair_fight' => $isFairFight,
                    'respect_gain' => $respectGain,
                    'data' => $attack,
                ]
            );
            $savedCount++;
        }

        $this->info("Synced {$savedCount} attacks for war {$war->war_id}");
        return $savedCount;
    }
}
