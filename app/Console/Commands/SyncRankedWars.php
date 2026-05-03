<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\RankedWar;
use App\Models\WarMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncRankedWars extends Command
{
    protected $signature = 'torn:sync-wars {faction_id?}';
    protected $description = 'Sync ranked wars from Torn API';

    public function handle(TornApiService $tornApi, FFScouterService $ffscouter): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing ranked wars for faction {$factionId}...");
        $log = DataRefreshLog::logStart('ranked_wars');

        $data = $tornApi->getRankedWars($factionId);

        if (!$data || !isset($data['rankedwars'])) {
            $this->error('Failed to fetch ranked wars.');
            return Command::FAILURE;
        }

        $warCount = 0;
        $memberCount = 0;

        // Only sync wars that are pending, accepted, or in progress
        $activeStates = ['pending', 'accepted', 'in progress'];
        
        foreach ($data['rankedwars'] as $warId => $war) {
            $warInfo = $war['war'] ?? [];
            
            // Skip finished/lost wars
            if (isset($warInfo['winner']) && $warInfo['winner'] > 0) {
                continue;
            }
            
            $factions = $war['factions'] ?? [];

            $opponentId = null;
            $opponentName = null;
            $scoreOurs = null;
            $scoreThem = null;

            foreach ($factions as $oppId => $oppData) {
                if ((string)$oppId !== (string)$factionId) {
                    $opponentId = $oppId;
                    $opponentName = $oppData['name'] ?? null;
                    $scoreThem = $oppData['score'] ?? null;
                } else {
                    $scoreOurs = $oppData['score'] ?? 0;
                }
            }

            $status = 'pending';
            if (isset($warInfo['winner']) && $warInfo['winner'] > 0) {
                $status = ((string)$warInfo['winner'] === (string)$factionId) ? 'won' : 'lost';
            } elseif ($scoreOurs > 0 || $scoreThem > 0) {
                $status = 'in progress';
            }

            RankedWar::updateOrCreate(
                [
                    'war_id' => $warId,
                    'faction_id' => $factionId,
                ],
                [
                    'opponent_faction_id' => $opponentId,
                    'opponent_faction_name' => $opponentName,
                    'status' => $status,
                    'start_date' => isset($warInfo['start']) ? now()->createFromTimestamp($warInfo['start']) : null,
                    'end_date' => isset($warInfo['end']) && $warInfo['end'] > 0 ? now()->createFromTimestamp($warInfo['end']) : null,
                    'score_ours' => $scoreOurs,
                    'score_them' => $scoreThem,
                    'data' => $war,
                ]
            );

            foreach ($factions as $oppFactionId => $oppData) {
                $memberData = $tornApi->getFactionMembers((int)$oppFactionId);
                if ($memberData && isset($memberData['members'])) {
                    $playerIds = array_keys($memberData['members']);
                    
                    $ffResults = $ffscouter->getStats($playerIds);
                    $ffIndex = [];
                    foreach ($ffResults as $ff) {
                        $ffIndex[$ff['player_id']] = $ff;
                    }
                    
                    foreach ($memberData['members'] as $playerId => $member) {
                        $warScore = $oppData['score'] ?? 0;
                        
                        $ffData = $ffIndex[$playerId] ?? null;
                        $ffScore = $ffData['fair_fight'] ?? null;
                        $estimatedStats = $ffData['bs_estimate_human'] ?? null;
                        $ffUpdatedAt = isset($ffData['last_updated']) ? now()->createFromTimestamp($ffData['last_updated']) : null;
                        
                        $statusColor = $member['status']['color'] ?? null;
                        $statusDesc = $member['status']['description'] ?? '';
                        
                        // Check previous member record
                        $oldMember = WarMember::where('war_id', $warId)
                            ->where('player_id', $playerId)
                            ->first();
                        
                        // Determine travel states
                        $isTraveling = preg_match('/^Traveling to .+/', $statusDesc);
                        $isReturning = $statusDesc === 'Returning to Torn';
                        
                        $oldStatus = $oldMember?->status_description ?? '';
                        $wasTraveling = preg_match('/^Traveling to .+/', $oldStatus);
                        $wasReturning = $oldStatus === 'Returning to Torn';
                        
                        // Calculate travel_started_at: any transition to travel → set, from travel → clear
                        $newTravelStartedAt = null;
                        if (($isTraveling || $isReturning) && (!$wasTraveling && !$wasReturning)) {
                            // Starting new journey
                            $newTravelStartedAt = now();
                        } elseif (($isTraveling || $isReturning) && ($oldMember?->travel_started_at)) {
                            // Already traveling, keep existing timestamp
                            $newTravelStartedAt = $oldMember->travel_started_at;
                        } elseif (($wasTraveling || $wasReturning) && (!$isTraveling && !$isReturning)) {
                            // Journey ended, clear
                            $newTravelStartedAt = null;
                        } elseif (($isTraveling || $isReturning) && !$oldMember?->travel_started_at) {
                            // Currently traveling but no timestamp - backfill (started before our capture)
                            $newTravelStartedAt = now();
                        }
                        
                        WarMember::updateOrCreate(
                            [
                                'war_id' => $warId,
                                'faction_id' => $oppFactionId,
                                'player_id' => $playerId,
                            ],
                            [
                                'name' => $member['name'] ?? null,
                                'level' => $member['level'] ?? 1,
                                'rank' => $member['rank'] ?? null,
                                'position' => $member['position'] ?? null,
                                'days_in_faction' => $member['days_in_faction'] ?? null,
                                'status_color' => $statusColor,
                                'status_description' => $member['status']['description'] ?? null,
                                'war_score' => $warScore,
                                'ff_score' => $ffScore,
                                'estimated_stats' => $estimatedStats,
                                'ff_updated_at' => $ffUpdatedAt,
                                'online_status' => $member['last_action']['status'] ?? null,
                                'online_description' => $member['last_action']['description'] ?? null,
                                'data' => $member,
                                'travel_started_at' => $newTravelStartedAt,
                            ]
                        );
                        $memberCount++;
                    }
                }
            }

            $warCount++;
        }

        $this->info("Synced {$warCount} ranked wars with {$memberCount} member records.");
        
        // Keep war members permanently for historical reference
        // (members should reflect the war's state, not current faction)
        
        $log->markComplete($memberCount);
        return Command::SUCCESS;
    }
}
