<?php

namespace App\Http\Controllers;

use App\Models\RankedWar;
use App\Models\WarMember;
use App\Models\WarAttack;
use App\Models\FactionSettings;
use App\Services\TornApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WarApiController extends Controller
{
    public function liveData(int $warId): JsonResponse
    {
        $factionId = FactionSettings::value('faction_id');

        $war = RankedWar::where('war_id', $warId)
            ->where('faction_id', $factionId)
            ->first();

        if (!$war) {
            return response()->json(['error' => 'War not found'], 404);
        }

        $ourMembers = WarMember::where('war_id', $warId)
            ->where('faction_id', $factionId)
            ->orderByDesc('level')
            ->get();

        $opponentFactionId = $war->opponent_faction_id;
        $opponentMembers = $war->opponent_faction_id
            ? WarMember::where('war_id', $warId)
                ->where('faction_id', $opponentFactionId)
                ->orderByDesc('level')
                ->get()
            : collect();

        return response()->json([
            'war' => [
                'status' => $war->status,
                'score_ours' => $war->score_ours,
                'score_them' => $war->score_them,
                'start_date' => $war->start_date?->toIso8601String(),
                'end_date' => $war->end_date?->toIso8601String(),
                'last_synced_at' => now()->toIso8601String(),
            ],
            'our_members' => $ourMembers->map(fn($m) => [
                'player_id' => $m->player_id,
                'name' => $m->name,
                'level' => $m->level,
                'ff_score' => $m->ff_score,
                'estimated_stats' => $m->estimated_stats,
                'online_status' => $m->online_status,
                'online_description' => $m->online_description,
                'status_color' => $m->status_color,
                'status_description' => $m->status_description,
                'hospital_until' => $m->data['status']['until'] ?? null,
                'last_synced_at' => $m->last_synced_at?->toIso8601String(),
                'personal_war_score' => $m->data['personalstats']['rankedwarhits'] ?? 0,
            ]),
            'opponent_members' => $opponentMembers->map(fn($m) => [
                'player_id' => $m->player_id,
                'name' => $m->name,
                'level' => $m->level,
                'ff_score' => $m->ff_score,
                'estimated_stats' => $m->estimated_stats,
                'online_status' => $m->online_status,
                'online_description' => $m->online_description,
                'status_color' => $m->status_color,
                'status_description' => $m->status_description,
                'hospital_until' => $m->data['status']['until'] ?? null,
                'last_synced_at' => $m->last_synced_at?->toIso8601String(),
                'personal_war_score' => $m->data['personalstats']['rankedwarhits'] ?? 0,
            ]),
        ]);
    }

    public function attacks(int $warId, TornApiService $api): JsonResponse
    {
        $factionId = FactionSettings::value('faction_id');

        $war = RankedWar::where('war_id', $warId)
            ->where('faction_id', $factionId)
            ->first();

        if (!$war) {
            return response()->json(['error' => 'War not found'], 404);
        }

        $settings = FactionSettings::first();
        Log::info("Attack sync check", [
            'war_id' => $warId,
            'war_status' => $war->status,
            'has_api_key' => !empty($settings?->torn_api_key)
        ]);
        
        if ($settings && $settings->torn_api_key && in_array($war->status, ['pending', 'accepted', 'in progress'])) {
            // Use faction attacks endpoint instead of user attacks
            $warStartTimestamp = $war->start_date?->timestamp ?? 0;
            $now = time();

            $warMembers = WarMember::where('war_id', $warId)->get()->keyBy('player_id');
            $warFactionIds = $warMembers->pluck('faction_id')->unique()->values()->toArray();

            $savedCount = 0;
            $allAttacks = [];
            $chunkSize = 2 * 3600; // 2 hours
            $from = $warStartTimestamp;

            while ($from < $now) {
                $to = min($from + $chunkSize, $now);
                $data = $api->getFactionAttacks($settings->faction_id, $settings->torn_api_key, $from, $to);

                if ($data && isset($data['attacks']) && !empty($data['attacks'])) {
                    $count = count($data['attacks']);
                    
                    // If we hit 100, split chunk in half and retry
                    if ($count >= 100 && $chunkSize > 1800) {
                        $halfSize = (int)($chunkSize / 2);
                        $halfFrom = $from;
                        while ($halfFrom < $to) {
                            $halfTo = min($halfFrom + $halfSize, $to);
                            $halfData = $api->getFactionAttacks($settings->faction_id, $settings->torn_api_key, $halfFrom, $halfTo);
                            if ($halfData && isset($halfData['attacks'])) {
                                foreach ($halfData['attacks'] as $attack) {
                                    $allAttacks[] = $attack;
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

            // Sort by timestamp descending
            usort($allAttacks, function($a, $b) {
                $tsA = $a['timestamp_started'] ?? 0;
                $tsB = $b['timestamp_started'] ?? 0;
                return $tsB <=> $tsA;
            });

            foreach ($allAttacks as $attack) {
                // Parse attack data from faction/attacks endpoint format
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

                // Get fair_fight modifier from nested structure
                $fairFight = $attack['modifiers']['fair_fight'] ?? $attack['fair_fight'] ?? 1.0;
                $isFairFight = $fairFight > 1.0;

                // Handle stealthed attacks (attacker identity hidden)
                if ($isMissingAttacker) {
                    $attackerName = 'Stealthed';
                    $attackerId = 0;
                    $respectGain = 0;
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
                    'war_id' => $warId,
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
        }

        $attacks = WarAttack::where('war_id', $warId)
            ->orderByDesc('timestamp')
            ->get()
            ->map(fn($a) => [
                'attacker_id' => $a->attacker_id,
                'attacker_name' => $a->attacker_name,
                'defender_id' => $a->defender_id,
                'defender_name' => $a->defender_name,
                'result' => $a->result,
                'fair_fight' => $a->fair_fight,
                'score_change' => $a->respect_gain,
                'timestamp' => $a->timestamp?->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'attacks' => $attacks,
            'synced_at' => now()->toIso8601String(),
        ]);
    }
}
