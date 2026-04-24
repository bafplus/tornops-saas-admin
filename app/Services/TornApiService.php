<?php

namespace App\Services;

use App\Models\FactionSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TornApiService
{
    private ?string $apiKey = null;
    private string $baseUrl = 'https://api.torn.com';
    private int $rateLimit = 100;

    public function __construct()
    {
        // Skip loading API key if tables don't exist yet (during setup)
        if (!\Schema::hasTable('faction_settings')) {
            return;
        }

        try {
            $settings = FactionSettings::first();
            if ($settings && $settings->torn_api_key) {
                $this->apiKey = $settings->torn_api_key;
            }
        } catch (\Exception $e) {
            $this->apiKey = null;
        }
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function get(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        // Always fetch fresh API key from database for each request
        if (!$apiKey) {
            try {
                $settings = \App\Models\FactionSettings::first();
                if ($settings && $settings->torn_api_key) {
                    $apiKey = $settings->torn_api_key;
                }
            } catch (\Exception $e) {
                // Table might not exist yet
            }
        }
        
        $key = $apiKey ?? $this->apiKey;
        $cacheKey = 'torn_api_' . md5($endpoint . json_encode($params) . $key);

        $cacheTtl = $this->getCacheTtl($endpoint);

        // Log API call first (always, even if cached)
        $this->logApiCall($endpoint, $params);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($endpoint, $params, $key) {

            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                    'key' => $key
                ]));

            if ($response->failed()) {
                Log::error('Torn API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('Torn API Error', [
                    'endpoint' => $endpoint,
                    'error' => $data['error']
                ]);
                return null;
            }

            return $data;
        });
    }

    public function getFaction(int $factionId, string $selections = 'info,members,stats,wars,rankedwars'): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => $selections]);
    }

    public function getFactionMembers(int $factionId): ?array
    {
        $data = $this->get("faction/{$factionId}");
        if ($data && isset($data['members'])) {
            return ['members' => $data['members']];
        }
        return $data;
    }

    public function getFactionChain(int $factionId): ?array
    {
        $data = $this->getNoCache("faction/{$factionId}", ['selections' => 'chain']);
        if ($data && isset($data['chain'])) {
            return $data['chain'];
        }
        return null;
    }

    public function getRankedWars(int $factionId, bool $noCache = false): ?array
    {
        $data = $noCache 
            ? $this->getNoCache("faction/{$factionId}", ['selections' => 'rankedwars'])
            : $this->get("faction/{$factionId}", ['selections' => 'rankedwars']);
        if ($data && isset($data['rankedwars'])) {
            return ['rankedwars' => $data['rankedwars']];
        }
        return $data;
    }
    
    public function getNoCache(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;
        
        // Log API call
        $this->logApiCall($endpoint, $params);
        
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/{$endpoint}", array_merge(['key' => $key], $params));

        if ($response->failed()) {
            Log::error('Torn API Error (no cache)', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error (no cache)', [
                'endpoint' => $endpoint,
                'error' => $data['error']
            ]);
            return null;
        }

        return $data;
    }

    public function getRankedWarReport(int $factionId, int $warId): ?array
    {
        return $this->get("faction/{$factionId}", [
            'selections' => 'rankedwarreport',
            'war' => $warId
        ]);
    }

    public function getPlayer(int $playerId, string $selections = 'profile,battlestats,personalstats,attacks', ?string $apiKey = null): ?array
    {
        return $this->get("user/{$playerId}", ['selections' => $selections], $apiKey);
    }

    public function getPlayersOnlineStatus(array $playerIds): array
    {
        $results = [];
        $uncachedIds = [];
        
        foreach ($playerIds as $playerId) {
            $cacheKey = "player_online_{$playerId}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $results[$playerId] = $cached;
            } else {
                $uncachedIds[] = $playerId;
            }
        }
        
        if (!empty($uncachedIds)) {
            foreach ($uncachedIds as $playerId) {
                $data = $this->getFresh("user/{$playerId}", ['selections' => 'profile']);
                if ($data && isset($data['last_action']['status'])) {
                    $statusData = [
                        'online_status' => $data['last_action']['status'] ?? 'Offline',
                        'online_description' => $data['last_action']['relative'] ?? null,
                    ];
                } else {
                    $statusData = [
                        'online_status' => 'Offline',
                        'online_description' => null,
                    ];
                }
                $results[$playerId] = $statusData;
                Cache::put("player_online_{$playerId}", $statusData, 180);
            }
        }
        
        return $results;
    }

    public function getFresh(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;

        $this->logApiCall($endpoint, $params);

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                'key' => $key
            ]));

        if ($response->failed()) {
            Log::error('Torn API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error', [
                'endpoint' => $endpoint,
                'error' => $data['error']
            ]);
            return null;
        }

        return $data;
    }

    public function getWars(int $factionId): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => 'wars']);
    }

public function getWarfare(int $factionId): ?array
{
return $this->get("faction/{$factionId}", ['selections' => 'warfare']);
}

public function getFactionContributors(int $factionId, string $stat = 'drugoverdoses'): ?array
{
return $this->getNoCache("faction/{$factionId}", ['selections' => 'contributors', 'stat' => $stat]);
}

public function getFactionCrimes(int $factionId): ?array
{
return $this->get("v2/faction/{$factionId}", ['selections' => 'crimes']);
}

public function getItems(): ?array
{
$settings = \App\Models\FactionSettings::first();
$apiKey = $settings?->torn_api_key;
return $this->getNoCache("v2/torn/items", [], $apiKey);
}

public function getFactionAttacks(int $factionId, ?string $apiKey = null, ?int $from = null, ?int $to = null): ?array
{
$params = ['selections' => 'attacks'];
if ($from) {
$params['from'] = $from;
}
if ($to) {
$params['to'] = $to;
}
return $this->get("faction/{$factionId}", $params, $apiKey);
}

public function getUserAttacksFull(string $apiKey, int $limit = 1000): ?array
{
$response = Http::timeout(10)
->get("{$this->baseUrl}/v2/user/attacksfull", [
'key' => $apiKey,
'limit' => $limit
]);

if ($response->failed()) {
Log::error('Torn V2 API Error', [
'endpoint' => "v2/user/attacksfull",
'status' => $response->status(),
'body' => $response->body()
]);
return null;
}

$data = $response->json();

if (isset($data['error'])) {
Log::error('Torn V2 API Error', [
'endpoint' => "v2/user/attacksfull",
'error' => $data['error']
]);
return null;
}

return $data;
}

    public function getAllRankedWars(): ?array
    {
        return $this->get("torn", ['selections' => 'rankedwars']);
    }

    public function clearCache(string $endpoint = null): void
    {
        if ($endpoint) {
            Cache::flush();
        } else {
            Cache::flush();
        }
    }

    private function getCacheTtl(string $endpoint): int
    {
        $ttls = config('services.torn.cache_ttl', [
            'faction' => 900,
            'members' => 900,
            'wars' => 300,
            'ranked_wars' => 300,
            'player' => 1800,
        ]);

        if (str_contains($endpoint, 'members')) {
            return $ttls['members'] ?? 900;
        }
        if (str_contains($endpoint, 'wars') || str_contains($endpoint, 'war')) {
            return $ttls['wars'] ?? 300;
        }
        if (str_contains($endpoint, 'ranked')) {
            return $ttls['ranked_wars'] ?? 300;
        }
        if (str_contains($endpoint, 'user') || str_contains($endpoint, 'player')) {
            return $ttls['player'] ?? 1800;
        }

        return 300;
    }

    private function logApiCall(string $endpoint, array $params): void
    {
        // Get current job context if available
        $jobCommand = null;
        if (app()->bound('Illuminate\Console\Command')) {
            try {
                $command = app(Illuminate\Console\Command::class);
                $jobCommand = $command->getName();
            } catch (\Exception $e) {
                // Not in command context
            }
        }
        
        // Log to database for history tracking, cleanup old records (keep max 24h)
        try {
            \App\Models\ApiCallLog::where('created_at', '<', now()->subHours(24))->delete();
            
            \App\Models\ApiCallLog::create([
                'endpoint' => $endpoint,
                'job_command' => $jobCommand,
                'calls_count' => 1,
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet
        }
        
        // Also use file-based cache for last minute counter
        $key = 'api_calls_last_minute';
        $calls = Cache::remember($key, 70, function () {
            return [];
        });
        $calls[] = ['endpoint' => $endpoint, 'job' => $jobCommand, 'time' => time()];
        $calls = array_filter($calls, fn($c) => $c['time'] > time() - 60);
        Cache::put($key, $calls, 120);
    }

    public static function getApiCallsLastMinute(): int
    {
        $calls = Cache::get('api_calls_last_minute', []);
        return count(array_filter($calls, fn($c) => $c['time'] > time() - 60));
    }

    public static function resetApiCallsCounter(): void
    {
        Cache::forget('api_calls_last_minute');
    }

    public function getUserMeritsV1(int $playerId, ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;
        if (!$key) {
            return null;
        }

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/user/{$playerId}", [
                'key' => $key,
                'selections' => 'merits'
            ]);

        if ($response->failed()) {
            Log::error('Torn API Error (merits V1)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error (merits V1)', [
                'error' => $data['error']
            ]);
            return null;
        }

        return $data['merits'] ?? null;
    }

    public function getUserMeritsV2(?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;
        if (!$key) {
            return null;
        }

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/v2/user/merits", [
                'key' => $key
            ]);

        if ($response->failed()) {
            Log::error('Torn API Error (merits V2)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error (merits V2)', [
                'error' => $data['error']
            ]);
            return null;
        }

        return $data['merits'] ?? null;
    }

    public function getStocks(?string $apiKey = null): ?array
    {
        if (!$apiKey) {
            Log::error('Torn V2 API Error (stocks)', ['error' => 'No API key provided']);
            return null;
        }

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/v2/torn/stocks", ['key' => $apiKey]);

        if ($response->failed()) {
            Log::error('Torn V2 API Error (stocks)', [
                'endpoint' => 'v2/torn/stocks',
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn V2 API Error (stocks)', [
                'endpoint' => 'v2/torn/stocks',
                'error' => $data['error']
            ]);
            return null;
        }

        return $data['stocks'] ?? null;
    }

    public function getUserStocks(string $apiKey): ?array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/v2/user/stocks", ['key' => $apiKey]);

        if ($response->failed()) {
            Log::error('Torn V2 API Error (user stocks)', [
                'endpoint' => 'v2/user/stocks',
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn V2 API Error (user stocks)', [
                'endpoint' => 'v2/user/stocks',
                'error' => $data['error']
            ]);
            return null;
        }

        return $data['stocks'] ?? null;
    }

    public function getUserBars(string $apiKey): ?array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/v2/user/bars", ['key' => $apiKey]);

        if ($response->failed()) {
            Log::error('Torn V2 API Error (user bars)', [
                'endpoint' => 'v2/user/bars',
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn V2 API Error (user bars)', [
                'endpoint' => 'v2/user/bars',
                'error' => $data['error']
            ]);
            return null;
        }

        return $data['bars'] ?? null;
    }

    public function getUserStats(string $apiKey): ?array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/v2/user", ['key' => $apiKey, 'selections' => 'battlestats']);

        if ($response->failed()) {
            Log::error('Torn V2 API Error (user stats)', [
                'endpoint' => 'v2/user',
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn V2 API Error (user stats)', [
                'endpoint' => 'v2/user',
                'error' => $data['error']
            ]);
            return null;
        }

        return $data;
    }
}
