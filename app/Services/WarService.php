<?php

namespace App\Services;

use App\Models\RankedWar;

class WarService
{
    public static function hasActiveWar(): bool
    {
        return RankedWar::whereIn('status', ['accepted', 'in progress'])
            ->where('faction_id', self::getFactionId())
            ->exists();
    }

    public static function hasPendingWar(): bool
    {
        return RankedWar::where('status', 'pending')
            ->where('faction_id', self::getFactionId())
            ->exists();
    }

    public static function isWarActive(): bool
    {
        return self::hasActiveWar();
    }

    public static function shouldThrottleApiCalls(): bool
    {
        return self::hasActiveWar();
    }

    public static function canFetchNonEssentialData(): bool
    {
        return !self::hasActiveWar();
    }

    public static function getActiveWar()
    {
        return RankedWar::whereIn('status', ['accepted', 'in progress'])
            ->where('faction_id', self::getFactionId())
            ->first();
    }

    protected static function getFactionId(): ?int
    {
        return \App\Models\FactionSettings::value('faction_id');
    }

    public static function calculateBaseRespect(int $level): float
    {
        if ($level <= 0) {
            return 0.25;
        }

        $baseRespect = 0.25 + (log($level) * 0.15);

        $lookup = [
            1 => 0.25, 10 => 0.83, 20 => 1.00, 45 => 1.20, 100 => 1.40
        ];

        foreach ($lookup as $lvl => $respect) {
            if ($level <= $lvl) {
                return $respect;
            }
        }

        return min($respect, 1.40);
    }

    public static function calculateRespectScore(int $level, float $ffScore): float
    {
        // Not used anymore - kept for potential future use
        return $level * $ffScore;
    }

    public static function getTopTargets(array $members, int $count = 3, ?float $userFfScore = null): array
    {
        $userFfScore = $userFfScore ?? 1.0;
        $maxFfScore = $userFfScore * 1.5;

        $scored = array_map(function ($member) use ($maxFfScore) {
            $ffScore = $member['ff_score'] ?? 1.0;
            $level = $member['level'] ?? 1;
            $statusColor = $member['status_color'] ?? '';
            $data = $member['data'] ?? [];
            $statusData = $data['status'] ?? [];
            $until = $statusData['until'] ?? 0;
            $remaining = $until > 0 ? max(0, $until - time()) : 0;
            
            $member['respect_score'] = self::calculateRespectScore($level, $ffScore);
            $member['attackable'] = $ffScore <= $maxFfScore;
            
            $isAvailable = true;
            if (in_array($statusColor, ['blue', 'red'])) {
                $isAvailable = $remaining <= 300;
            }
            $member['is_available'] = $isAvailable;
            
            return $member;
        }, $members);

        $attackableTargets = array_values(array_filter($scored, fn($m) => ($m['attackable'] ?? false) && ($m['is_available'] ?? true)));

        if (empty($attackableTargets)) {
            $attackableTargets = array_values(array_filter($scored, fn($m) => $m['attackable'] ?? false));
        }

        if (empty($attackableTargets)) {
            return array_slice($scored, 0, $count);
        }

        usort($attackableTargets, function ($a, $b) {
            return ($b['ff_score'] ?? 0) <=> ($a['ff_score'] ?? 0);
        });

        return array_slice($attackableTargets, 0, $count);
    }
}
