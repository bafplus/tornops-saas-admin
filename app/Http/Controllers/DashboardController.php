<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\RankedWar;
use App\Models\WarAttack;
use App\Models\WarChain;
use App\Models\OrganizedCrimeSlot;
use App\Services\WarService;
use App\Services\OCService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $totalMembers = FactionMember::where('faction_id', $settings->faction_id ?? 0)->count();
        $activeWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->whereNull('winner')
            ->orWhere('winner', 0)
            ->count();
        $recentWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();
        
        $ocAlerts = OCService::getActiveOCs();

        $twoWeeksAgo = now()->subDays(14)->timestamp;
        
        $ocParticipation = DB::table('organized_crime_slots')
            ->select('user_id', DB::raw('MAX(user_joined_at) as last_oc'))
            ->whereNotNull('user_id')
            ->groupBy('user_id');
        
        $inactiveMembers = FactionMember::where('faction_id', $settings->faction_id ?? 0)
            ->leftJoinSub($ocParticipation, 'ocs', function ($join) {
                $join->on('faction_members.player_id', '=', 'ocs.user_id');
            })
            ->where(function($query) use ($twoWeeksAgo) {
                $query->where('last_oc', '<', $twoWeeksAgo)
                      ->orWhereNull('last_oc');
            })
            ->select('faction_members.player_id', 'faction_members.name', 'last_oc')
            ->orderBy('last_oc')
            ->limit(20)
            ->get();

        return view('dashboard.index', compact('settings', 'totalMembers', 'activeWars', 'recentWars', 'ocAlerts', 'inactiveMembers'));
    }

public function members()
    {
        $settings = FactionSettings::first();
        $members = FactionMember::where('faction_id', $settings->faction_id ?? 0)
            ->orderByRaw("CASE WHEN status_color = 'green' THEN 0 WHEN status_color = 'red' THEN 1 WHEN status_color = 'blue' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN (status_color = 'red' OR status_color = 'blue') AND data IS NOT NULL THEN json_extract(data, '$.status.until') ELSE 999999999999 END")
            ->orderBy('name')
            ->paginate(25);
        
        // Add property_name, revivable, and icons from player_profiles
        $playerIds = $members->pluck('player_id')->toArray();
        $propertyMap = [];
        $revivableMap = [];
        $iconsMap = [];
        if (!empty($playerIds)) {
            $profiles = DB::table('player_profiles')
                ->whereIn('player_id', $playerIds)
                ->select('player_id', 'property_name', 'revivable', 'icons')
                ->get();
            foreach ($profiles as $p) {
                $propertyMap[$p->player_id] = $p->property_name;
                $revivableMap[$p->player_id] = $p->revivable;
                $iconsMap[$p->player_id] = $p->icons;
            }
        }
        foreach ($members as $m) {
            $m->property_name = $propertyMap[$m->player_id] ?? null;
            $m->revivable = isset($revivableMap[$m->player_id]) && $revivableMap[$m->player_id];
            $m->icons = $iconsMap[$m->player_id] ?? null;
        }
        
        $warActive = WarService::hasActiveWar();
        
        $travelMethod = FactionSettings::value('travel_method', 1);
        
        return view('dashboard.members', compact('settings', 'members', 'warActive', 'travelMethod'));
    }

    public function wars()
    {
        $settings = FactionSettings::first();
        $activeWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->where(function($q) {
                $q->whereNull('winner')->orWhere('winner', 0);
            })
            ->orderBy('start_date', 'desc')
            ->get();
        $pastWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->whereNotNull('winner')
            ->where('winner', '>', 0)
            ->orderBy('start_date', 'desc')
            ->limit(20)
            ->get();

        return view('dashboard.wars', compact('settings', 'activeWars', 'pastWars'));
    }

    public function warDetail($warId)
    {
        $settings = FactionSettings::first();
        $war = RankedWar::where('war_id', $warId)
            ->where('faction_id', $settings->faction_id ?? 0)
            ->firstOrFail();
        
$ourMembers = $war->members()
            ->where('faction_id', $settings->faction_id)
            ->orderByRaw("CASE WHEN status_color = 'green' THEN 0 WHEN status_color = 'red' THEN 1 WHEN status_color = 'blue' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN (status_color = 'red' OR status_color = 'blue') AND data IS NOT NULL THEN json_extract(data, '$.status.until') ELSE 999999999999 END")
            ->orderBy('name')
            ->get();
        
        // Add property_name from player_profiles
        $ourPlayerIds = $ourMembers->pluck('player_id')->toArray();
        $propertyMap = [];
        if (!empty($ourPlayerIds)) {
            $profiles = DB::table('player_profiles')
                ->whereIn('player_id', $ourPlayerIds)
                ->select('player_id', 'property_name')
                ->get();
            foreach ($profiles as $p) {
                $propertyMap[$p->player_id] = $p->property_name;
            }
        }
        foreach ($ourMembers as $m) {
            $m->property_name = $propertyMap[$m->player_id] ?? null;
        }
        
        $opponentMembers = $war->members()
            ->where('faction_id', $war->opponent_faction_id)
            ->orderByRaw("CASE WHEN status_color = 'green' THEN 0 WHEN status_color = 'red' THEN 1 WHEN status_color = 'blue' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN (status_color = 'red' OR status_color = 'blue') AND data IS NOT NULL THEN json_extract(data, '$.status.until') ELSE 999999999999 END")
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                $member->respect_score = \App\Services\WarService::calculateRespectScore(
                    $member->level ?? 1,
                    $member->ff_score ?? 1.0
                );
                return $member;
            });
        
        // Add property_name to opponent members
        $oppPlayerIds = $opponentMembers->pluck('player_id')->toArray();
        $oppPropertyMap = [];
        if (!empty($oppPlayerIds)) {
            $oppProfiles = DB::table('player_profiles')
                ->whereIn('player_id', $oppPlayerIds)
                ->select('player_id', 'property_name')
                ->get();
            foreach ($oppProfiles as $p) {
                $oppPropertyMap[$p->player_id] = $p->property_name;
            }
        }
        foreach ($opponentMembers as $m) {
            $m->property_name = $oppPropertyMap[$m->player_id] ?? null;
        }
        
        // Get user FF score from their player_id in faction members
        $userFfScore = 1.0;
        $userId = Auth::id();
        if ($userId) {
            $tornPlayerId = \App\Models\User::find($userId)?->torn_player_id;
            if ($tornPlayerId) {
                // Find this player in faction members to get their FF score
                $memberFf = \App\Models\FactionMember::where('player_id', $tornPlayerId)->value('ff_score');
                $userFfScore = $memberFf ?? 1.0;
            }
        }
        
        // Get top targets filtered by this user's FF score
        $topTargets = \App\Services\WarService::getTopTargets(
            $opponentMembers->toArray(),
            3,
            $userFfScore
        );
        $topTargetIds = array_column($topTargets, 'player_id');
        
        $ourFactionId = $settings->faction_id;
    $oppFactionId = $war->opponent_faction_id;
    $ourMemberIds = $war->members()->where('faction_id', $ourFactionId)->pluck('player_id');
    $oppMemberIds = $war->members()->where('faction_id', $oppFactionId)->pluck('player_id');

    $attackStats = WarAttack::where('war_id', $warId)
        ->whereIn('attacker_id', $ourMemberIds)
        ->whereIn('defender_id', $oppMemberIds)
        ->selectRaw('attacker_id,
            COUNT(*) as total_attacks,
            SUM(CASE WHEN result = "Attacked" OR result = "Hospitalized" THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN result = "Lost" OR result = "Stalemate" THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN result = "Interrupted" THEN 1 ELSE 0 END) as interrupted,
            SUM(respect_gain) as total_score,
            MAX(respect_gain) as max_single')
        ->groupBy('attacker_id')
        ->get()
        ->keyBy('attacker_id');

    $totalHits = $attackStats->sum('successful');
    $topHitter = $attackStats->sortByDesc('successful')->first();
    $topRespect = $attackStats->sortByDesc('max_single')->first();

    $attacks = WarAttack::where('war_id', $warId)->orderByDesc('timestamp')->paginate(10);

    $retaliationTargets = WarAttack::where('war_id', $warId)
        ->whereIn('defender_id', $ourMemberIds)
        ->whereIn('attacker_id', $oppMemberIds)
        ->whereIn('result', ['Attacked', 'Hospitalized'])
        ->where('timestamp', '>=', now()->subMinutes(5))
        ->orderByDesc('timestamp')
        ->get()
        ->map(function ($attack) {
            $attackTime = $attack->timestamp;
            $windowEnd = $attackTime->copy()->addSeconds(300);
            $retaliationHit = WarAttack::where('war_id', $attack->war_id)
                ->where('attacker_id', $attack->defender_id)
                ->where('defender_id', $attack->attacker_id)
                ->where('timestamp', '>', $attackTime)
                ->where('timestamp', '<=', $windowEnd)
                ->whereIn('result', ['Attacked', 'Hospitalized'])
                ->exists();

            return [
                'target_id' => $attack->attacker_id,
                'target_name' => $attack->attacker_name,
                'attacked_by' => $attack->defender_name,
                'timestamp' => $attackTime,
                'expires_at' => $windowEnd,
                'retaliated' => $retaliationHit,
            ];
        })
        ->filter(fn($r) => !$r['retaliated']);

    $ourChain = WarChain::where('war_id', $warId)->where('faction_id', $ourFactionId)->first();
    $oppChain = WarChain::where('war_id', $warId)->where('faction_id', $oppFactionId)->first();
    $chainStats = [
        'chain_hits' => $ourChain?->chain_hits ?? 0,
        'max_chain' => $ourChain?->max_chain ?? 0,
        'chain_respect' => $ourChain?->chain_respect ?? 0,
        'avg_bonus' => 1.0,
    ];

    $activeChain = null;
    if ($ourChain && $ourChain->expires_at && $ourChain->expires_at > now()) {
        $activeChain = [
            'level' => $ourChain->current_chain,
            'expires_at' => $ourChain->expires_at,
            'chain_hits' => $ourChain->chain_hits ?? 0,
            'max_chain' => $ourChain->max_chain ?? 0,
            'chain_respect' => $ourChain->chain_respect ?? 0,
            'avg_bonus' => 1.0,
            'faction_name' => $settings->faction_name ?? 'Our Faction',
        ];
    }

    $oppActiveChain = null;
    if ($oppChain && $oppChain->expires_at && $oppChain->expires_at > now()) {
        $oppActiveChain = [
            'level' => $oppChain->current_chain,
            'expires_at' => $oppChain->expires_at,
            'chain_hits' => $oppChain->chain_hits ?? 0,
            'max_chain' => $oppChain->max_chain ?? 0,
            'chain_respect' => $oppChain->chain_respect ?? 0,
            'avg_bonus' => 1.0,
            'faction_name' => $war->opponent_faction_name ?? 'Opponent',
        ];
    }

    $travelMethod = FactionSettings::value('travel_method', 1);

    $ourPlayerIds = $war->members()
        ->where('faction_id', $settings->faction_id)
        ->pluck('player_id')
        ->toArray();

    $topHitterName = 'N/A';
    $topHitterHits = 0;
    $topRespectName = 'N/A';
    $topRespectVal = 0;

    foreach ($ourPlayerIds as $playerId) {
        if (isset($attackStats[$playerId])) {
            $stats = $attackStats[$playerId];
            if ($stats->successful > $topHitterHits) {
                $topHitterHits = $stats->successful;
                $topHitterName = \App\Models\FactionMember::where('player_id', $playerId)->first()?->name ?? 'ID:' . $playerId;
            }
            if ($stats->max_single > $topRespectVal) {
                $topRespectVal = $stats->max_single;
                $topRespectName = \App\Models\FactionMember::where('player_id', $playerId)->first()?->name ?? 'ID:' . $playerId;
            }
        }
    }

    return view('dashboard.war-detail', compact('settings', 'war', 'ourMembers', 'opponentMembers', 'attackStats', 'attacks', 'retaliationTargets', 'chainStats', 'activeChain', 'oppActiveChain', 'travelMethod', 'topTargetIds', 'totalHits', 'topHitterName', 'topHitterHits', 'topRespectName', 'topRespectVal'));
    }

    public function warStats(int $warId)
    {
        $settings = FactionSettings::first();
        
        $attackStats = WarAttack::where('war_id', $warId)
            ->selectRaw('attacker_id, 
                         COUNT(*) as total_attacks,
                         SUM(CASE WHEN result = "Attacked" OR result = "Hospitalized" THEN 1 ELSE 0 END) as successful,
                         SUM(CASE WHEN result = "Lost" OR result = "Stalemate" THEN 1 ELSE 0 END) as failed,
                         SUM(CASE WHEN result = "Interrupted" THEN 1 ELSE 0 END) as interrupted,
                         SUM(respect_gain) as total_score')
            ->groupBy('attacker_id')
            ->get()
            ->keyBy('attacker_id');

$stats = [];
    foreach ($attackStats as $playerId => $stat) {
        $stats[$playerId] = [
            'hits' => (int) $stat->successful,
            'successful' => (int) $stat->successful,
            'failed' => (int) $stat->failed,
            'interrupted' => (int) $stat->interrupted,
            'score' => round($stat->total_score, 2)
        ];
    }

        return response()->json([
            'stats' => $stats,
            'synced_at' => now()->toIso8601String()
        ]);
    }
}
