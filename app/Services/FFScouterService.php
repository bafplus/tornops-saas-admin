<?php

namespace App\Services;

use App\Models\FactionSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FFScouterService
{
    private ?string $apiKey = null;
    private string $baseUrl = 'https://ffscouter.com/api/v1';
    private int $cacheTtl = 3600;

    public function __construct()
    {
        $settings = FactionSettings::first();
        if ($settings) {
            $this->apiKey = $settings->ffscouter_api_key;
        }
    }

    public function getPlayerStats(int $playerId): ?array
    {
        $result = $this->getStats([$playerId]);
        return $result[0] ?? null;
    }

    public function getStats(array $playerIds): array
    {
        if (!$this->apiKey || empty($playerIds)) {
            return [];
        }

        $cacheKey = 'ffscouter_stats_' . md5(implode(',', $playerIds));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($playerIds) {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/get-stats", [
                    'key' => $this->apiKey,
                    'targets' => implode(',', $playerIds),
                ]);

            if ($response->failed()) {
                Log::error('FFScouter API Error', [
                    'player_ids' => $playerIds,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('FFScouter API Error', [
                    'player_ids' => $playerIds,
                    'error' => $data['error'],
                ]);
                return [];
            }

            return $data;
        });
    }

    public function checkKey(): ?array
    {
        if (!$this->apiKey) {
            return null;
        }

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/check-key", [
                'key' => $this->apiKey,
            ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}
