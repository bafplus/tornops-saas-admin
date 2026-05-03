<?php

namespace App\Http\Controllers;

use App\Models\MeritDefinition;
use App\Services\TornApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeritPlannerController extends Controller
{
    protected TornApiService $tornApi;

    public function __construct(TornApiService $tornApi)
    {
        $this->tornApi = $tornApi;
    }

    public function index()
    {
        $user = Auth::user();

        // Auto-fetch if no merits exist OR data is stale (>24 hours)
        $fetchError = null;
        $merits = \DB::table('user_merits')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('merit_name');

        $stale = false;
        if ($merits->isNotEmpty()) {
            $firstMerit = $merits->first();
            $stale = $firstMerit->updated_at && \Carbon\Carbon::parse($firstMerit->updated_at)->lt(now()->subHours(24));
        }
        
        if (($merits->isEmpty() || $stale) && $user->torn_api_key && $user->torn_player_id) {
            $fetchError = $this->fetchMeritsFromApi($user);
            // Re-fetch from DB after auto-fetch
            $merits = \DB::table('user_merits')
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('merit_name');
        }

        $allMerits = MeritDefinition::getAllMeritNames();
        $groupedMerits = [];

        foreach (MeritDefinition::$categories as $categoryKey => $categoryName) {
            $categoryMerits = MeritDefinition::getMeritsByCategory($categoryKey);
            
            $groupedMerits[$categoryName] = [];
            
            foreach ($categoryMerits as $name => $definition) {
                $meritData = $merits[$name] ?? null;
                
                $currentLevel = $meritData->current_level ?? 0;
                $plannedLevel = $meritData->planned_level ?? $currentLevel;
                
                $currentCost = MeritDefinition::calculateCost(0, $currentLevel);
                $plannedCost = MeritDefinition::calculateCost(0, $plannedLevel);
                $costToPlan = $plannedCost - $currentCost;
                
                $currentBonus = MeritDefinition::calculateBonus($name, $currentLevel);
                $plannedBonus = MeritDefinition::calculateBonus($name, $plannedLevel);
                
                $groupedMerits[$categoryName][] = [
                    'name' => $name,
                    'description' => $definition['description'],
                    'current_level' => $currentLevel,
                    'planned_level' => $plannedLevel,
                    'current_bonus' => $currentBonus,
                    'planned_bonus' => $plannedBonus,
                    'cost_to_plan' => $costToPlan,
                    'has_changes' => $plannedLevel !== $currentLevel,
                ];
            }
        }

        $totalPlannedCost = 0;
        foreach ($merits as $merit) {
            $plannedCost = MeritDefinition::calculateCost(0, $merit->planned_level);
            $totalPlannedCost += $plannedCost;
        }

        $availablePoints = $user->merit_points_available ?? 0;
        $usedPoints = $user->merit_points_used ?? 0;
        $totalPointsAvailable = $availablePoints + $usedPoints;
        $extraNeeded = max(0, $totalPlannedCost - $totalPointsAvailable);

        return view('merit-planner.index', [
            'groupedMerits' => $groupedMerits,
            'availablePoints' => $availablePoints,
            'usedPoints' => $user->merit_points_used ?? 0,
            'totalPlannedCost' => $totalPlannedCost,
            'extraNeeded' => $extraNeeded,
            'hasData' => $merits->isNotEmpty(),
            'fetchError' => $fetchError,
        ]);
    }

    private function fetchMeritsFromApi($user): ?string
    {
        if (!$user->torn_api_key || !$user->torn_player_id) {
            return null;
        }

        try {
            $v1Data = $this->tornApi->getUserMeritsV1($user->torn_player_id, $user->torn_api_key);
            $v2Data = $this->tornApi->getUserMeritsV2($user->torn_api_key);

            if (!$v1Data || !is_array($v1Data)) {
                return 'Failed to fetch merits from V1 API';
            }

            if (!$v2Data || !isset($v2Data['available'])) {
                return 'Failed to fetch merits from V2 API';
            }

            $user->merit_points_available = $v2Data['available'] ?? 0;
            $user->merit_points_used = $v2Data['used'] ?? 0;
            $user->save();

            $allMerits = MeritDefinition::getAllMeritNames();
            foreach ($allMerits as $meritName) {
                $currentLevel = $v1Data[$meritName] ?? 0;
                // Preserve existing planned_level, only update current_level
                $existing = \DB::table('user_merits')
                    ->where('user_id', $user->id)
                    ->where('merit_name', $meritName)
                    ->first();
                $plannedLevel = $existing?->planned_level ?? $currentLevel;
                \DB::table('user_merits')->updateOrInsert(
                    ['user_id' => $user->id, 'merit_name' => $meritName],
                    ['current_level' => $currentLevel, 'planned_level' => $plannedLevel, 'updated_at' => now()]
                );
            }

            return null;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function fetch(Request $request)
    {
        $user = Auth::user();

        if (!$user->torn_api_key) {
            return back()->with('error', 'No API key found. Please add your Torn API key in settings.');
        }

        if (!$user->torn_player_id) {
            return back()->with('error', 'No player ID found. Please complete setup first.');
        }

        $error = $this->fetchMeritsFromApi($user);

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Merits fetched successfully!');
    }

    public function updatePlanned(Request $request)
    {
        try {
            $user = Auth::user();
            $meritName = $request->input('merit_name');
            $change = (int) $request->input('planned_level', 0);

            $merit = \DB::table('user_merits')
                ->where('user_id', $user->id)
                ->where('merit_name', $meritName)
                ->first();

            if (!$merit) {
                // Check if user has any merits at all
                $hasAnyMerits = \DB::table('user_merits')->where('user_id', $user->id)->exists();
                if (!$hasAnyMerits) {
                    return response()->json(['error' => 'No merit data found. Please click "Try again" to fetch your merits from Torn first.'], 404);
                }
                return response()->json(['error' => 'Merit not found: ' . $meritName], 404);
            }

            // Handle relative change (+1, -1) or absolute level (0-10)
            if (in_array($change, [-1, 1])) {
                // Relative change
                $newLevel = $merit->planned_level + $change;
            } else {
                // Absolute level (from bar click)
                $newLevel = $change;
            }

            // Clamp between 0 and 10
            $newLevel = max(0, min(10, $newLevel));

            \DB::table('user_merits')
                ->where('user_id', $user->id)
                ->where('merit_name', $meritName)
                ->update(['planned_level' => $newLevel, 'updated_at' => now()]);

            // Re-fetch the updated merit to ensure we have latest data
            $updatedMerit = \DB::table('user_merits')
                ->where('user_id', $user->id)
                ->where('merit_name', $meritName)
                ->first();

            $currentCost = MeritDefinition::calculateCost(0, $updatedMerit->current_level);
            $newPlannedCost = MeritDefinition::calculateCost(0, $newLevel);
            $costToPlan = $newPlannedCost - $currentCost;

            $totalPlannedCost = 0;
            $allMerits = \DB::table('user_merits')->where('user_id', $user->id)->get();
            foreach ($allMerits as $m) {
                $totalPlannedCost += MeritDefinition::calculateCost(0, $m->planned_level);
            }

            $availablePoints = (int) ($user->merit_points_available ?? 0);
            $usedPoints = (int) ($user->merit_points_used ?? 0);
            $totalPointsAvailable = $availablePoints + $usedPoints;
            $extraNeeded = max(0, $totalPlannedCost - $totalPointsAvailable);

            return response()->json([
                'success' => true,
                'planned_level' => $newLevel,
                'cost_to_plan' => $costToPlan,
                'planned_bonus' => MeritDefinition::calculateBonus($meritName, $newLevel),
                'total_planned_cost' => $totalPlannedCost,
                'available_points' => $availablePoints,
                'used_points' => $usedPoints,
                'extra_needed' => $extraNeeded,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function resetPlanned()
    {
        $user = Auth::user();

        \DB::table('user_merits')
            ->where('user_id', $user->id)
            ->update([
                'planned_level' => \DB::raw('current_level'),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Planned levels reset to current levels.');
    }
}
