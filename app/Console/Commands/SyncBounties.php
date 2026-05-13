<?php

namespace App\Console\Commands;

use App\Models\Bounty;
use App\Models\DataRefreshLog;
use App\Models\FactionSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncBounties extends Command
{
    protected $signature = 'torn:sync-bounties';
    protected $description = 'Sync all active bounties from Torn API to database';

    public function handle(): int
    {
        $apiKey = FactionSettings::value('torn_api_key');
        if (!$apiKey) {
            $this->error('No API key configured.');
            return Command::FAILURE;
        }

        $log = DataRefreshLog::logStart('bounties');
        $this->info('Fetching bounties from Torn API...');

        $allBounties = [];
        $offset = 0;
        $page = 0;

        while (true) {
            $page++;
            $this->line("Fetching page {$page} (offset={$offset})...");

            try {
                $response = Http::timeout(15)->get("https://api.torn.com/v2/torn/bounties", [
                    'key' => $apiKey,
                    'limit' => 100,
                    'offset' => $offset,
                ]);

                if ($response->failed()) {
                    $this->error("API error on page {$page}: " . $response->status());
                    $log->fail('API error on page ' . $page);
                    return Command::FAILURE;
                }

                $data = $response->json();

                if (!$data || !isset($data['bounties']) || empty($data['bounties'])) {
                    break;
                }

                foreach ($data['bounties'] as $b) {
                    $allBounties[] = [
                        'target_id' => $b['target_id'],
                        'target_name' => $b['target_name'],
                        'target_level' => $b['target_level'],
                        'lister_id' => $b['lister_id'] ?? null,
                        'lister_name' => $b['lister_name'] ?? null,
                        'reward' => $b['reward'],
                        'reason' => $b['reason'] ?? null,
                        'quantity' => $b['quantity'] ?? 1,
                        'is_anonymous' => $b['is_anonymous'] ?? false,
                        'valid_until' => $b['valid_until'],
                        'last_synced_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($data['bounties']) < 100) {
                    break;
                }

                $offset += 100;
                usleep(250000); // 250ms delay
            } catch (\Exception $e) {
                $this->error("Exception on page {$page}: " . $e->getMessage());
                $log->fail('Exception: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info("Fetched " . count($allBounties) . " total bounties across {$page} pages.");

        // Truncate and bulk insert
        Bounty::truncate();
        $chunks = array_chunk($allBounties, 500);
        foreach ($chunks as $chunk) {
            Bounty::insert($chunk);
        }

        $this->info("Inserted " . count($allBounties) . " bounties into database.");
        $log->markComplete(count($allBounties));

        return Command::SUCCESS;
    }
}
