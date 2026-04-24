<?php

namespace App\Models;

class MeritDefinition
{
    public const CATEGORY_FIGHTING_STATS = 'fighting_stats';
    public const CATEGORY_WEAPON_MASTERY = 'weapon_mastery';
    public const CATEGORY_COMBAT = 'combat';
    public const CATEGORY_CRIME = 'crime';
    public const CATEGORY_ECONOMIC = 'economic';
    public const CATEGORY_EDUCATION = 'education';
    public const CATEGORY_OTHER = 'other';

    public static array $categories = [
        self::CATEGORY_FIGHTING_STATS => 'Fighting Stats',
        self::CATEGORY_WEAPON_MASTERY => 'Weapon Mastery',
        self::CATEGORY_COMBAT => 'Combat',
        self::CATEGORY_CRIME => 'Crime',
        self::CATEGORY_ECONOMIC => 'Economic',
        self::CATEGORY_EDUCATION => 'Education',
        self::CATEGORY_OTHER => 'Other',
    ];

    public static array $merits = [
        // Fighting Stats (3% per level)
        'Brawn' => [
            'category' => self::CATEGORY_FIGHTING_STATS,
            'description' => 'Gives a passive bonus to strength of 3% per level',
            'bonus_type' => 'strength',
            'bonus_per_level' => 3,
            'bonus_unit' => '%',
        ],
        'Protection' => [
            'category' => self::CATEGORY_FIGHTING_STATS,
            'description' => 'Gives a passive bonus to defense of 3% per level',
            'bonus_type' => 'defense',
            'bonus_per_level' => 3,
            'bonus_unit' => '%',
        ],
        'Sharpness' => [
            'category' => self::CATEGORY_FIGHTING_STATS,
            'description' => 'Gives a passive bonus to speed of 3% per level',
            'bonus_type' => 'speed',
            'bonus_per_level' => 3,
            'bonus_unit' => '%',
        ],
        'Evasion' => [
            'category' => self::CATEGORY_FIGHTING_STATS,
            'description' => 'Gives a passive bonus to dexterity of 3% per level',
            'bonus_type' => 'dexterity',
            'bonus_per_level' => 3,
            'bonus_unit' => '%',
        ],

        // Weapon Mastery (1% damage + 0.2 accuracy per level)
        'Heavy Artillery Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'heavy_artillery',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Machine Gun Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'machine_gun',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Rifle Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'rifle',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'SMG Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'smg',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Shotgun Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'shotgun',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Pistol Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'pistol',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Club Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'club',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Piercing Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'piercing',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Slashing Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'slashing',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Mechanical Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'mechanical',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],
        'Temporary Mastery' => [
            'category' => self::CATEGORY_WEAPON_MASTERY,
            'description' => 'Increases damage by 1% and accuracy by 0.2 per level',
            'bonus_type' => 'temporary',
            'bonus_per_level' => 1,
            'bonus_unit' => '% damage',
            'secondary_bonus' => 0.2,
            'secondary_unit' => ' accuracy',
        ],

        // Combat
        'Critical Hit Rate' => [
            'category' => self::CATEGORY_COMBAT,
            'description' => 'Increases critical hit rate by 0.5% per level',
            'bonus_type' => 'crit',
            'bonus_per_level' => 0.5,
            'bonus_unit' => '%',
        ],
        'Life Points' => [
            'category' => self::CATEGORY_COMBAT,
            'description' => 'Increases maximum life by 5% per level',
            'bonus_type' => 'hp',
            'bonus_per_level' => 5,
            'bonus_unit' => '%',
        ],
        'Nerve Bar' => [
            'category' => self::CATEGORY_COMBAT,
            'description' => 'Increases maximum nerve bar by 1 point per level',
            'bonus_type' => 'nerve',
            'bonus_per_level' => 1,
            'bonus_unit' => ' point',
        ],
        'Stealth' => [
            'category' => self::CATEGORY_COMBAT,
            'description' => 'Increases stealth during outgoing attacks by +0.2 per level',
            'bonus_type' => 'stealth',
            'bonus_per_level' => 0.2,
            'bonus_unit' => '',
        ],
        'Hospitalizing' => [
            'category' => self::CATEGORY_COMBAT,
            'description' => 'Increases hospitalization time by 5% per level',
            'bonus_type' => 'hospital',
            'bonus_per_level' => 5,
            'bonus_unit' => '%',
        ],

        // Crime
        'Crime XP' => [
            'category' => self::CATEGORY_CRIME,
            'description' => 'Increases crime experience and skill gain by 1% per level',
            'bonus_type' => 'crime',
            'bonus_per_level' => 1,
            'bonus_unit' => '%',
        ],
        'Masterful Looting' => [
            'category' => self::CATEGORY_CRIME,
            'description' => 'Increases money gained from mugging by 5% per level',
            'bonus_type' => 'looting',
            'bonus_per_level' => 5,
            'bonus_unit' => '%',
        ],

        // Economic
        'Bank Interest' => [
            'category' => self::CATEGORY_ECONOMIC,
            'description' => 'Increases bank interest by 5% per level',
            'bonus_type' => 'bank',
            'bonus_per_level' => 5,
            'bonus_unit' => '%',
        ],
        'Awareness' => [
            'category' => self::CATEGORY_ECONOMIC,
            'description' => 'Increases frequency of items appearing in the city by 20% per level',
            'bonus_type' => 'awareness',
            'bonus_per_level' => 20,
            'bonus_unit' => '%',
        ],

        // Education
        'Education Length' => [
            'category' => self::CATEGORY_EDUCATION,
            'description' => 'Decreases education course length by 2% per level',
            'bonus_type' => 'education',
            'bonus_per_level' => 2,
            'bonus_unit' => '%',
        ],
        'Employee Effectiveness' => [
            'category' => self::CATEGORY_EDUCATION,
            'description' => 'Increases employee effectiveness by +1 per level',
            'bonus_type' => 'employee',
            'bonus_per_level' => 1,
            'bonus_unit' => '',
        ],

        // Other
        'Addiction Mitigation' => [
            'category' => self::CATEGORY_OTHER,
            'description' => 'Reduces the negative effects of addiction by 2% per level',
            'bonus_type' => 'addiction',
            'bonus_per_level' => 2,
            'bonus_unit' => '%',
        ],
    ];

    public static function getMerit(string $name): ?array
    {
        return self::$merits[$name] ?? null;
    }

    public static function getMeritsByCategory(string $category): array
    {
        return array_filter(self::$merits, fn($merit) => $merit['category'] === $category);
    }

    public static function getCategoryName(string $category): string
    {
        return self::$categories[$category] ?? $category;
    }

    public static function getAllMeritNames(): array
    {
        return array_keys(self::$merits);
    }

    public static function calculateCost(int $currentLevel, int $targetLevel): int
    {
        if ($targetLevel <= $currentLevel) {
            return 0;
        }

        $totalCost = 0;
        for ($level = $currentLevel + 1; $level <= $targetLevel; $level++) {
            $totalCost += $level;
        }

        return $totalCost;
    }

    public static function calculateBonus(string $meritName, int $level): string
    {
        $merit = self::getMerit($meritName);
        if (!$merit || $level <= 0) {
            return '0' . ($merit['bonus_unit'] ?? '');
        }

        $bonus = $level * $merit['bonus_per_level'];
        $result = $bonus . $merit['bonus_unit'];

        if (isset($merit['secondary_bonus'])) {
            $secondary = $level * $merit['secondary_bonus'];
            $result .= ' / ' . $secondary . $merit['secondary_unit'];
        }

        return $result;
    }

    public static function getBonusAmount(string $meritName, int $level): float
    {
        $merit = self::getMerit($meritName);
        if (!$merit) {
            return 0;
        }
        return $level * $merit['bonus_per_level'];
    }
}
