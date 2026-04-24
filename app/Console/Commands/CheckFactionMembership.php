<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\User;
use App\Services\TornApiService;
use Illuminate\Console\Command;

class CheckFactionMembership extends Command
{
    protected $signature = 'torn:check-faction-membership';

    protected $description = 'Check if users are still members of the faction and disable accounts if not';

    public function handle(): int
    {
        $settings = FactionSettings::first();

        if (!$settings || !$settings->torn_api_key) {
            $this->error('Faction settings or API key not configured.');
            return self::FAILURE;
        }

        $tornApi = new TornApiService();
        
        $factionData = $tornApi->getFaction($settings->faction_id, 'members');

        if (!$factionData || !isset($factionData['members'])) {
            $this->error('Could not fetch faction members from Torn API.');
            return self::FAILURE;
        }

        $factionMemberIds = array_keys($factionData['members']);
        
        $activeUsers = User::where('status', User::STATUS_ACTIVE)->get();
        
        $disabledCount = 0;

        foreach ($activeUsers as $user) {
            if (!in_array($user->torn_player_id, $factionMemberIds)) {
                $this->info("Disabling user {$user->name} (ID: {$user->torn_player_id}) - no longer in faction");
                $user->disable();
                $disabledCount++;
            }
        }

        $invitedUsers = User::where('status', User::STATUS_INVITED)->get();
        
        foreach ($invitedUsers as $user) {
            if (!in_array($user->torn_player_id, $factionMemberIds)) {
                $this->info("Disabling invited user {$user->name} (ID: {$user->torn_player_id}) - no longer in faction");
                $user->disable();
                $disabledCount++;
            }
        }

        if ($disabledCount > 0) {
            $this->info("Disabled {$disabledCount} user(s) who are no longer in the faction.");
        } else {
            $this->info('All users are still members of the faction.');
        }

        return self::SUCCESS;
    }
}