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

        $url = "https://api.torn.com/v2/user/events?striptags=false&limit=100";
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
                // Always advance last run so we don't re-fetch empty results
                AdminSetting::set('last_event_run', (string) now()->timestamp);
                $this->info('No new events. Last run timestamp updated.');
                return Command::SUCCESS;
            }

            $processed = 0;
            $matched = 0;

            foreach ($events as $event) {
                $eventId = $event['id'] ?? null;
                $timestamp = $event['timestamp'] ?? null;
                $eventHtml = $event['event'] ?? '';

                if (!$eventId || !$timestamp) {
                    continue;
                }

                if (PaymentHistory::where('event_id', $eventId)->exists()) {
                    continue;
                }

                $parsed = $this->parseReceivedItem($eventHtml);
                if (!$parsed) {
                    continue;
                }

                [$payerName, $payerId, $itemName, $quantity, $description] = $parsed;

                $factionId = null;
                $matchedInstance = false;

                if (preg_match('/faction[:\s]*(\d+)/i', $description, $m)) {
                    $factionId = $m[1];
                } elseif (preg_match('/\b(\d{4,})\b/', $description, $m)) {
                    $possibleId = $m[1];
                    $faction = Faction::where('torn_faction_id', $possibleId)
                        ->orWhere('id', $possibleId)->first();
                    if ($faction) $factionId = $faction->id;
                }

                if (!$factionId) {
                    foreach (Faction::pluck('slug')->toArray() as $slug) {
                        if (stripos($description, $slug) !== false) {
                            $f = Faction::where('slug', $slug)->first();
                            if ($f) { $factionId = $f->id; break; }
                        }
                    }
                }

                if ($factionId) {
                    $matchedInstance = true;
                    $matched++;
                    $this->processPayment((int) $factionId, $itemName, $quantity, $timestamp);
                }

                PaymentHistory::create([
                    'faction_id' => $factionId,
                    'event_id' => $eventId,
                    'item_name' => $itemName,
                    'quantity' => $quantity,
                    'description' => $description,
                    'payer_name' => $payerName,
                    'payer_id' => $payerId,
                    'extension_days' => $quantity * 7,
                    'expires_at' => $factionId ? Faction::find($factionId)?->expires_at : null,
                    'matched_instance' => $matchedInstance,
                ]);

                $processed++;
            }

            // Always update last run to current time so we don't re-fetch
            AdminSetting::set('last_event_run', (string) now()->timestamp);

            $this->info("Processed {$processed} events, {$matched} matched to instances.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Payment sync failed: ' . $e->getMessage());
            $this->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function parseReceivedItem(string $html): ?array
    {
        // Strip HTML tags to get plain text
        $text = strip_tags($html);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Pattern: "PlayerName sent you Xx ItemName. (Description: ...)"
        // Or: "PlayerName sent you Xx ItemName"
        if (!preg_match('/^\s*([A-Za-z0-9_\-\[\]]+)\s+sent\s+you\s+(\d+)x\s+([A-Za-z0-9\s\-]+?)(?:\.\s*$|\.\s*\(|$)/i', $text, $m)) {
            return null;
        }

        $payerName = trim($m[1]);
        $quantity = (int) $m[2];
        $itemName = trim($m[3]);

        // Try to extract player ID from the HTML link
        $payerId = null;
        if (preg_match('/profiles\.php\?XID=(\d+)/', $html, $idMatch)) {
            $payerId = (int) $idMatch[1];
        }

        // Extract description from parentheses after the item name
        $description = '';
        if (preg_match('/\.\s*\((.*?)\)\s*$/', $text, $descMatch)) {
            $description = trim($descMatch[1]);
        }
        // Also try "Description:" prefix
        if (empty($description) && preg_match('/Description:\s*(.*?)$/i', $text, $descMatch)) {
            $description = trim($descMatch[1]);
        }

        return [$payerName, $payerId, $itemName, $quantity, $description];
    }

    private function processPayment(int $factionId, string $itemName, int $quantity, int $timestamp): void
    {
        $faction = Faction::find($factionId);
        if (!$faction) return;

        $now = now();
        $extensionDays = $quantity * 7;

        if ($faction->expires_at && $faction->expires_at->isFuture()) {
            $newExpiry = $faction->expires_at->copy()->addDays($extensionDays);
        } else {
            $newExpiry = $now->copy()->addDays($extensionDays);
        }

        if (!$faction->subscription_start) {
            $faction->subscription_start = $now;
        }

        $faction->expires_at = $newExpiry;
        $faction->subscription_type = 'paid';
        $faction->save();

        $this->info("Updated faction {$faction->name}: expires {$newExpiry->format('Y-m-d')}");
    }
}
