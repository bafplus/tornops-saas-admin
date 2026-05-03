<?php

namespace App\Jobs;

use App\Models\RankedWar;
use App\Models\WarAttack;
use App\Models\FactionSettings;
use App\Services\TornApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWarAttacks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $warId;

    public function __construct(int $warId)
    {
        $this->warId = $warId;
    }

    public function handle(TornApiService $api): void
    {
        $settings = FactionSettings::first();
        if (!$settings || !$settings->torn_api_key) {
            return;
        }

        $war = RankedWar::where('war_id', $this->warId)
            ->where('faction_id', $settings->faction_id)
            ->first();

        if (!$war) {
            return;
        }

        $data = $api->getWarAttacksV2($this->warId, $settings->torn_api_key);

        if (!$data || !isset($data['attacks'])) {
            return;
        }

        $memberIds = $war->members()
            ->pluck('player_id')
            ->toArray();

        foreach ($data['attacks'] as $attack) {
            $attackerId = $attack['attacker_id'] ?? null;
            $defenderId = $attack['defender_id'] ?? null;

            if (!in_array($attackerId, $memberIds) && !in_array($defenderId, $memberIds)) {
                continue;
            }

            WarAttack::updateOrCreate(
                [
                    'war_id' => $this->warId,
                    'timestamp' => $attack['timestamp'] ?? now(),
                    'attacker_id' => $attackerId,
                    'defender_id' => $defenderId,
                ],
                [
                    'attacker_name' => $attack['attacker_name'] ?? 'Unknown',
                    'defender_name' => $attack['defender_name'] ?? 'Unknown',
                    'result' => $attack['result'] ?? null,
                    'stealthed' => $attack['stealthed'] ?? false,
                    'fair_fight' => $attack['fair_fight'] ?? false,
                    'respect_gain' => $attack['respect_gain'] ?? 0,
                    'data' => $attack,
                ]
            );
        }

        Log::info("Synced war attacks for war {$this->warId}");
    }
}