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

            // Always update to current time to avoid re-fetching
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
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Format: "You were sent Xx Item from Player" or "You were sent a Item from Player"
        if (!preg_match('/^You were sent (?:a|an|(\d+))x?\s+(.+?)\s+from\s+([A-Za-z0-9_\-\[\]]+)/i', $text, $m)) {
            return null;
        }

        $quantity = $m[1] !== '' ? (int) $m[1] : 1;
        $itemName = trim($m[2]);
        // Strip trailing punctuation from item name
        $itemName = preg_replace('/[.,;:!?]+$/', '', $itemName);
        $payerName = trim($m[3]);

        // Extract player ID from HTML link
        $payerId = null;
        if (preg_match('/profiles\.php\?XID=(\d+)/', $html, $idMatch)) {
            $payerId = (int) $idMatch[1];
        }

        // Extract description from "with the message: ..."
        $description = '';
        if (preg_match('/with the message:\s*(.*?)$/i', $text, $descMatch)) {
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

        // Sync to instance container using the same method as FactionService
        $slug = $faction->slug;
        $expiresStr = $newExpiry->format('Y-m-d H:i:s');
        $startStr = $faction->subscription_start->format('Y-m-d H:i:s');
        $paymentItem = $faction->payment_item ?? 'xanax';
        $paymentAmount = $faction->payment_amount ?? 1;

        $script = "<?php
\$pdo = new PDO('sqlite:/data/database.sqlite');
\$sql = \"UPDATE faction_settings SET
    subscription_status = 'active',
    subscription_start = COALESCE(subscription_start, datetime('now')),
    expires_at = '{$expiresStr}',
    payment_item = '{$paymentItem}',
    payment_amount = {$paymentAmount}
\";
\$pdo->exec(\$sql);
";
        $tmpFile = "/tmp/sync_{$slug}.php";
        file_put_contents($tmpFile, $script);
        exec("docker cp {$tmpFile} {$slug}:/tmp/sync.php 2>&1", $out, $cpCode);
        if ($cpCode === 0) {
            exec("docker exec {$slug} php /tmp/sync.php 2>&1", $execOut, $execCode);
            $this->info("Sync to {$slug}: " . ($execCode === 0 ? 'OK' : 'FAILED'));
        } else {
            $this->warn("Sync to {$slug}: docker cp failed");
        }
        @unlink($tmpFile);
    }
}
