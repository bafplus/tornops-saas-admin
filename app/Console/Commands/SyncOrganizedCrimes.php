<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\OrganizedCrime;
use App\Models\OrganizedCrimeSlot;
use App\Models\FactionMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use Illuminate\Console\Command;

class SyncOrganizedCrimes extends Command
{
    protected $signature = 'torn:sync-ocs {faction_id?} {--force : Force sync even during active war}';
    protected $description = 'Sync organized crimes from Torn API';

    public function handle(TornApiService $tornApi): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing organized crimes for faction {$factionId}...");
        $log = DataRefreshLog::logStart('organized_crimes');

        $data = $tornApi->getFactionCrimes($factionId);

        if (!$data || !isset($data['crimes'])) {
            $this->error('Failed to fetch organized crimes.');
            $log->markFailed('No data returned');
            return Command::FAILURE;
        }

        $crimes = $data['crimes'];
        $synced = 0;
        $slotsFilled = 0;
        $slotsOpen = 0;

        foreach ($crimes as $crimeData) {
            $oc = OrganizedCrime::updateOrCreate(
                ['faction_id' => $factionId, 'oc_id' => $crimeData['id']],
                [
                    'name' => $crimeData['name'],
                    'difficulty' => $crimeData['difficulty'],
                    'status' => strtolower($crimeData['status']),
                    'oc_created_at' => $crimeData['created_at'],
                    'previous_crime_id' => $crimeData['previous_crime_id'] ?? null,
                    'planning_started_at' => $crimeData['planning_at'],
                    'ready_at' => $crimeData['ready_at'],
                    'executed_at' => $crimeData['executed_at'],
                    'expires_at' => $crimeData['expired_at'],
                    'rewards' => isset($crimeData['rewards']) ? json_encode($crimeData['rewards']) : null,
                    'last_synced_at' => now(),
                ]
            );

            foreach ($crimeData['slots'] as $slotData) {
                $userId = $slotData['user']['id'] ?? null;
                $itemReq = $slotData['item_requirement'];
                $user = $slotData['user'] ?? [];
                
                OrganizedCrimeSlot::updateOrCreate(
                    ['oc_id' => $crimeData['id'], 'position' => $slotData['position'], 'position_number' => $slotData['position_number']],
                    [
                        'organized_crime_id' => $oc->id,
                        'position_id' => $slotData['position_id'] ?? null,
                        'user_id' => $userId,
                        'user_outcome' => $user['outcome'] ?? null,
                        'user_progress' => $user['progress'] ?? null,
                        'checkpoint_pass_rate' => $slotData['checkpoint_pass_rate'],
                        'user_joined_at' => $user['joined_at'] ?? null,
                        'item_required_id' => $itemReq['id'] ?? null,
                        'item_available' => $itemReq['is_available'] ?? false,
                        'item_outcome' => isset($user['item_outcome']) ? json_encode($user['item_outcome']) : null,
                        'last_synced_at' => now(),
                    ]
                );

                if ($userId) {
                    $slotsFilled++;
                } else {
                    $slotsOpen++;
                }
            }

            $synced++;
        }

        $activeOCs = OrganizedCrime::where('faction_id', $factionId)
            ->whereIn('status', ['planning', 'recruiting', 'ready'])
            ->count();

        $this->info("Synced {$synced} OCs ({$slotsFilled} filled, {$slotsOpen} open slots). Active: {$activeOCs}");

        $log->markComplete($synced);
        return Command::SUCCESS;
    }
}
