<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\RankedWar;
use App\Services\TornApiService;

class SyncPlayerProfiles extends Command
{
    protected $signature = 'torn:sync-profiles {--force : Force update all profiles}';
    protected $description = 'Sync player profiles from Torn API';

    protected $staleDays = 3;
    protected $batchSize = 10;
    protected $processed = 0;
    protected $updated = 0;
    protected $new = 0;
    protected $errors = 0;
    protected $rateLimited = false;
    protected $apiCallsThisMinute = 0;
    protected $callsPerMinute = 100;

    public function handle(): int
    {
        $this->info('Starting player profile sync...');

        $force = $this->option('force');
        
        // Get target player IDs
        $targetIds = $this->getTargetPlayerIds($force);
        
        if (empty($targetIds)) {
            $this->warn('No players to sync');
            return 0;
        }

        $this->info('Found ' . count($targetIds) . ' players to sync');

        // Get API keys
        $apiKeys = $this->getApiKeys();
        
        if (empty($apiKeys)) {
            $this->error('No API keys available');
            return 1;
        }

        $this->info('Using ' . count($apiKeys) . ' API key(s)');

        // Process in batches with key rotation
        $chunks = array_chunk($targetIds, $this->batchSize);
        
        foreach ($chunks as $batch) {
            foreach ($batch as $playerId) {
                $apiKey = $apiKeys[array_rand($apiKeys)];
                
                $this->processPlayer($playerId, $apiKey, $force);
                
                $this->processed++;
                
                // Rate limit handling
                if ($this->rateLimited) {
                    $this->warn('Rate limited, stopping for this run');
                    break 2;
                }
            }
        }

        $this->info("Sync complete: {$this->processed} processed, {$this->new} new, {$this->updated} updated, {$this->errors} errors");
        
        return 0;
    }

    protected function getTargetPlayerIds(bool $force = false): array
    {
        $settings = DB::table('faction_settings')->first();
        $factionId = $settings->faction_id ?? 0;
        
        // Get faction members from both war_members (active) and faction_members table
        $memberIds = DB::table('war_members')
            ->where('faction_id', $factionId)
            ->pluck('player_id')
            ->toArray();

        // Also get from faction_members table (always synced, not just during war)
        $factionMemberIds = DB::table('faction_members')
            ->where('faction_id', $factionId)
            ->pluck('player_id')
            ->toArray();

        // Merge all member IDs
        $memberIds = array_unique(array_merge($memberIds, $factionMemberIds));

        // Get opponents from active/planned wars
        $activeWars = DB::table('ranked_wars')
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->pluck('opponent_faction_id')
            ->toArray();

        $opponentIds = DB::table('war_members')
            ->whereIn('faction_id', $activeWars)
            ->pluck('player_id')
            ->toArray();

        // Combine and dedupe
        $targetIds = array_unique(array_merge($memberIds, $opponentIds));

        // Filter: only new or stale (>3 days)
        if (empty($targetIds)) {
            return [];
        }

        return $targetIds;
    }

    protected function getApiKeys(): array
    {
        $keys = User::whereNotNull('torn_api_key')
            ->where('torn_api_key', '!=', '')
            ->pluck('torn_api_key')
            ->toArray();
        
        if (empty($keys)) {
            $settings = DB::table('faction_settings')->first();
            if ($settings?->torn_api_key) {
                $keys = [$settings->torn_api_key];
            }
        }
        
        return $keys;
    }

    protected function processPlayer(int $playerId, string $apiKey, bool $force): void
    {
        if ($this->apiCallsThisMinute >= $this->callsPerMinute) {
            $this->warn('Approaching rate limit, pausing...');
            sleep(60);
            $this->apiCallsThisMinute = 0;
        }
        
        try {
            $api = new TornApiService();
            $data = $api->getPlayer($playerId, 'profile', $apiKey);

            if (!$data || isset($data['error'])) {
                $this->error("API error for player {$playerId}: " . ($data['error']['error'] ?? 'Unknown'));
                $this->errors++;
                return;
            }

            $this->apiCallsThisMinute++;
            usleep(500000);
            $profile = $data;

            if (!$profile || !isset($profile['player_id'])) {
                $this->error("No profile data for player {$playerId}");
                $this->errors++;
                return;
            }

            // Determine if this is from a war opponent
            $isFromWar = $this->isWarOpponent($playerId);

            // Build record
            $record = [
                'player_id' => $profile['player_id'] ?? $playerId,
                'name' => $profile['name'] ?? null,
                'level' => $profile['level'] ?? 1,
                'rank' => $profile['rank'] ?? null,
                'title' => $profile['honor'] ?? null,
                'age' => $profile['age'] ?? null,
                'signed_up' => $profile['signup'] ?? null,
                'faction_id' => $profile['faction']['faction_id'] ?? null,
                'honor_id' => null,
                'property_id' => $profile['property_id'] ?? null,
                'property_name' => $profile['property'] ?? null,
                'donator_status' => $profile['donator'] ?? false,
                'image' => $profile['profile_image'] ?? null,
                'gender' => $profile['gender'] ?? null,
                'role' => $profile['role'] ?? null,
                'revivable' => $profile['revivable'] ?? false,
                'status_description' => $profile['status']['description'] ?? null,
                'status_details' => $profile['status']['details'] ?? null,
                'status_state' => $profile['status']['state'] ?? null,
                'status_color' => $profile['status']['color'] ?? null,
                'status_until' => $profile['status']['until'] ?? null,
                'spouse_id' => $profile['married']['spouse_id'] ?? null,
                'spouse_name' => $profile['married']['spouse_name'] ?? null,
                'spouse_status' => null,
                'awards' => $profile['awards'] ?? 0,
                'friends' => $profile['friends'] ?? 0,
                'enemies' => $profile['enemies'] ?? 0,
                'forum_posts' => $profile['forum_posts'] ?? 0,
                'karma' => $profile['karma'] ?? 0,
                'life_current' => $profile['life']['current'] ?? 0,
                'life_maximum' => $profile['life']['maximum'] ?? 0,
                'last_action_status' => $profile['last_action']['status'] ?? null,
                'last_action_timestamp' => $profile['last_action']['timestamp'] ?? null,
                'from_war' => $isFromWar ? 1 : 0,
                'data' => json_encode($profile),
                'last_synced_at' => now(),
                'updated_at' => now(),
            ];

            // Insert or update
            $existing = DB::table('player_profiles')->where('player_id', $playerId)->first();
            
            if ($existing) {
                DB::table('player_profiles')->where('player_id', $playerId)->update($record);
                $this->updated++;
            } else {
                $record['created_at'] = now();
                $record['name'] = $profile['name'] ?? 'Unknown';
                DB::table('player_profiles')->insert($record);
                $this->new++;
            }

        } catch (\Exception $e) {
            $this->error("Error for player {$playerId}: " . $e->getMessage());
            $this->errors++;
        }
    }

    protected function isWarOpponent(int $playerId): bool
    {
        $activeWars = DB::table('ranked_wars')
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->pluck('opponent_faction_id')
            ->toArray();

        if (empty($activeWars)) {
            return false;
        }

        $member = DB::table('war_members')
            ->where('player_id', $playerId)
            ->whereIn('faction_id', $activeWars)
            ->first();

        return $member !== null;
    }
}