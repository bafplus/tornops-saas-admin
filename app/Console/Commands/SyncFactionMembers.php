<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncFactionMembers extends Command
{
    protected $signature = 'torn:sync-members {faction_id?} {--force : Force sync even during active war}';
    protected $description = 'Sync faction members from Torn API';

    public function handle(TornApiService $tornApi, FFScouterService $ffscouter): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing members for faction {$factionId}...");
        $log = DataRefreshLog::logStart('faction_members');

        $data = $tornApi->getFactionMembers($factionId);

        if (!$data || !isset($data['members'])) {
            $this->error('Failed to fetch faction members.');
            return Command::FAILURE;
        }

        // Get FF scores - first check war_members, then use FF Scouter for missing
        $playerIds = array_keys($data['members']);
        
        // Get existing FF data from war_members table
        $existingFF = \App\Models\WarMember::whereIn('player_id', $playerIds)
            ->whereNotNull('ff_score')
            ->where('ff_score', '>', 0)
            ->get()
            ->unique('player_id')
            ->keyBy('player_id');

        // Only fetch FF Scouter for members without data
        $missingIds = array_filter($playerIds, fn($id) => !$existingFF->has($id));
        $ffIndex = [];
        
        if (!empty($missingIds)) {
            $ffResults = $ffscouter->getStats($missingIds);
            foreach ($ffResults as $ff) {
                $ffIndex[$ff['player_id']] = $ff;
            }
        }

        $count = 0;
        foreach ($data['members'] as $playerId => $member) {
            // Use existing data from war_members or new FF data
            if ($existingFF->has($playerId)) {
                $ffData = $existingFF->get($playerId);
                $ffScore = $ffData->ff_score;
                $estimatedStats = $ffData->estimated_stats;
            } elseif (isset($ffIndex[$playerId])) {
                $ffData = $ffIndex[$playerId];
                $ffScore = $ffData['fair_fight'] ?? null;
                $estimatedStats = $ffData['bs_estimate_human'] ?? null;
            } else {
                $ffScore = null;
                $estimatedStats = null;
            }
            
            FactionMember::updateOrCreate(
                [
                    'faction_id' => $factionId,
                    'player_id' => $playerId,
                ],
                [
                    'name' => $member['name'] ?? null,
                    'level' => $member['level'] ?? 1,
                    'rank' => $member['rank'] ?? null,
                    'position' => $member['position'] ?? null,
                    'days_in_faction' => $member['days_in_faction'] ?? null,
                    'status_description' => $member['status']['description'] ?? null,
                    'status_color' => $member['status']['color'] ?? null,
                    'online_status' => $member['last_action']['status'] ?? null,
                    'status_changed_at' => isset($member['status']['until']) && $member['status']['until'] > 0 
                        ? \Carbon\Carbon::createFromTimestamp($member['status']['until']) 
                        : null,
                    'ff_score' => $ffScore,
                    'estimated_stats' => $estimatedStats,
                    'data' => $member,
                    'last_synced_at' => now(),
                ]
            );

            // Track travel status
            $currentDesc = $member['status']['description'] ?? '';
            $isTraveling = preg_match('/^Traveling to .+/', $currentDesc);
            $isReturning = $currentDesc === 'Returning to Torn';
            
            // Check if member was previously traveling (if we have old data)
            $oldMember = FactionMember::where('faction_id', $factionId)
                ->where('player_id', $playerId)
                ->first();
            
            if ($oldMember) {
                $oldStatus = $oldMember->status_description ?? '';
                $wasTraveling = preg_match('/^Traveling to .+/', $oldStatus);
                $wasReturning = $oldStatus === 'Returning to Torn';
                
                // Any transition TO traveling or returning → start new journey
                if (($isTraveling || $isReturning) && (!$wasTraveling && !$wasReturning)) {
                    $oldMember->travel_started_at = now();
                    $oldMember->save();
                }
                // Transition FROM traveling/returning → anything else → clear timestamp
                elseif (($wasTraveling || $wasReturning) && (!$isTraveling && !$isReturning)) {
                    $oldMember->travel_started_at = null;
                    $oldMember->save();
                }
                // Currently traveling but no timestamp - backfill (started before our capture)
                elseif (($isTraveling || $isReturning) && !$oldMember->travel_started_at) {
                    $oldMember->travel_started_at = now();
                    $oldMember->save();
                }
            }
            $count++;
        }

        $skipped = count($missingIds);
        $reused = $count - $skipped;
        $this->info("Synced {$count} members ({$reused} from cache, {$skipped} from FF Scouter).");

        $currentMemberIds = array_keys($data['members']);
        $deleted = FactionMember::where('faction_id', $factionId)
            ->whereNotIn('player_id', $currentMemberIds)
            ->delete();
        
        if ($deleted > 0) {
            $this->info("Removed {$deleted} member(s) who are no longer in the faction.");
        }
        
        $log->markComplete($count);
        return Command::SUCCESS;
    }
}
