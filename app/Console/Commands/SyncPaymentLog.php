<?php

namespace App\Console\Commands;

use App\Models\AdminSetting;
use App\Models\Faction;
use App\Models\PaymentHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPaymentLog extends Command
{
    protected $signature = 'torn:sync-payments';
    protected $description = 'Fetch received items from Torn API events and process payments';

    public function handle(): int
    {
        $apiKey = AdminSetting::get('torn_api_key');
        if (empty($apiKey)) {
            $this->error('No API key configured in admin settings.');
            return Command::FAILURE;
        }

        $lastRun = AdminSetting::get('last_event_run');

        // Fetch events from Torn API
        $url = "https://api.torn.com/v2/user/events?striptags=false&limit=50";
        if ($lastRun) {
            $url .= "&from=" . $lastRun;
        }

        try {
            $response = Http::get($url, ['key' => $apiKey]);
            if (!$response->successful()) {
                $this->error('API request failed: ' . $response->body());
                return Command::FAILURE;
            }

            $data = $response->json();
            $events = $data['events'] ?? [];

            if (empty($events)) {
                $this->info('No new events.');
                return Command::SUCCESS;
            }

            $processed = 0;
            $matched = 0;
            $latestTimestamp = $lastRun;

            foreach ($events as $event) {
                $eventId = $event['id'] ?? null;
                $timestamp = $event['timestamp'] ?? null;
                $eventType = $event['type'] ?? '';
                $dataRaw = $event['data'] ?? [];

                if (!$eventId || !$timestamp) {
                    continue;
                }

                // Track latest timestamp for next run
                if ($timestamp > $latestTimestamp) {
                    $latestTimestamp = $timestamp;
                }

                // Only process ReceivedItem events
                if ($eventType !== 'ReceivedItem') {
                    continue;
                }

                // Check if already stored
                if (PaymentHistory::where('event_id', $eventId)->exists()) {
                    continue;
                }

                $itemName = $dataRaw['item_name'] ?? $dataRaw['item'] ?? '';
                $quantity = (int) ($dataRaw['quantity'] ?? $dataRaw['count'] ?? 1);
                $payerId = $dataRaw['player_id'] ?? $dataRaw['user_id'] ?? null;
                $payerName = $dataRaw['player_name'] ?? $dataRaw['username'] ?? '';
                $description = $dataRaw['description'] ?? '';

                // Try to match to a faction by description (contains faction ID or slug)
                $factionId = null;
                $matchedInstance = false;

                // Look for faction ID in description
                if (preg_match('/faction[:\s]*(\d+)/i', $description, $m)) {
                    $factionId = $m[1];
                } elseif (preg_match('/\b(\d{4,})\b/', $description, $m)) {
                    // Fallback: try to find a numeric ID in description
                    $possibleId = $m[1];
                    $faction = Faction::where('torn_faction_id', $possibleId)
                        ->orWhere('id', $possibleId)
                        ->first();
                    if ($faction) {
                        $factionId = $faction->id;
                    }
                }

                // Also check by payer ID (if they're an admin of an instance)
                if (!$factionId && $payerId) {
                    // Check if payer matches any faction admin by looking at related users
                    // For now, we'll try to match by description containing slug
                    $slugs = Faction::pluck('slug')->toArray();
                    foreach ($slugs as $slug) {
                        if (stripos($description, $slug) !== false) {
                            $faction = Faction::where('slug', $slug)->first();
                            if ($faction) {
                                $factionId = $faction->id;
                                break;
                            }
                        }
                    }
                }

                if ($factionId) {
                    $matchedInstance = true;
                    $matched++;
                    $this->processPayment($factionId, $itemName, $quantity, $timestamp);
                }

                // Store payment record
                PaymentHistory::create([
                    'faction_id' => $factionId,
                    'event_id' => $eventId,
                    'item_name' => $itemName,
                    'quantity' => $quantity,
                    'description' => $description,
                    'payer_name' => $payerName,
                    'payer_id' => $payerId,
                    'extension_days' => $quantity * 7,
                    'matched_instance' => $matchedInstance,
                ]);

                $processed++;
            }

            // Update last run timestamp only on success
            if ($latestTimestamp !== $lastRun) {
                AdminSetting::set('last_event_run', (string) $latestTimestamp);
            }

            $this->info("Processed {$processed} events, {$matched} matched to instances.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Payment sync failed: ' . $e->getMessage());
            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function processPayment(int $factionId, string $itemName, int $quantity, int $timestamp): void
    {
        $faction = Faction::find($factionId);
        if (!$faction) return;

        $now = now();
        $extensionDays = $quantity * 7;

        // Calculate new expiration
        if ($faction->expires_at && $faction->expires_at->isFuture()) {
            $newExpiry = $faction->expires_at->copy()->addDays($extensionDays);
        } else {
            $newExpiry = $now->copy()->addDays($extensionDays);
        }

        // If first payment, set subscription start
        if (!$faction->subscription_start) {
            $faction->subscription_start = $now;
        }

        $faction->expires_at = $newExpiry;
        $faction->subscription_type = 'paid';
        $faction->save();

        $this->info("Updated faction {$faction->name}: expires {$newExpiry->format('Y-m-d')}");
    }
}
