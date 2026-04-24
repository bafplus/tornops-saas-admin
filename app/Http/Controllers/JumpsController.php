<?php

namespace App\Http\Controllers;

use App\Services\TornApiService;
use App\Models\GymStatsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class JumpsController extends Controller
{
    public function index(TornApiService $tornApi)
    {
        $user = Auth::user();
        $apiKey = $user->torn_api_key;
        $playerId = $user->torn_player_id;

        if (!$apiKey || !$playerId) {
            return view('jumps.index', [
                'error' => 'No API key or player ID found. Please add your Torn API key in Settings.',
                'bars' => null,
                'stats' => null,
                'gym' => null,
            ]);
        }

        // Fetch user bars (happy, energy) using V2
        $bars = $tornApi->getUserBars($apiKey);
        
        // Fetch user gym and battle stats using V1 (same as Gym Assistant)
        try {
            $response = Http::timeout(10)->get('https://api.torn.com/user/' . $playerId, [
                'key' => $apiKey,
                'selections' => 'gym,battlestats'
            ]);

            if ($response->failed()) {
                return view('jumps.index', [
                    'error' => 'Failed to fetch user data from Torn API.',
                    'bars' => null,
                    'stats' => null,
                    'gym' => null,
                ]);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                $errorMsg = is_array($data['error']) ? ($data['error']['error'] ?? json_encode($data['error'])) : $data['error'];
                return view('jumps.index', [
                    'error' => 'API Error: ' . $errorMsg,
                    'bars' => null,
                    'stats' => null,
                    'gym' => null,
                ]);
            }
        } catch (\Exception $e) {
            return view('jumps.index', [
                'error' => 'Error: ' . $e->getMessage(),
                'bars' => null,
                'stats' => null,
                'gym' => null,
            ]);
        }

        // Extract battle stats
        $strength = $data['strength'] ?? 0;
        $defense = $data['defense'] ?? 0;
        $speed = $data['speed'] ?? 0;
        $dexterity = $data['dexterity'] ?? 0;
        $totalStats = $strength + $defense + $speed + $dexterity;

        // Extract gym info
        $gymId = $data['active_gym'] ?? null;
        $gymName = $this->getGymName($gymId);
        $gymData = $this->getGymData($gymId);

        // Extract bars
        $happy = $bars['happy'] ?? [];
        $energy = $bars['energy'] ?? [];
        $currentHappy = $happy['current'] ?? 0;
        $maxHappy = $happy['maximum'] ?? 0;
        $currentEnergy = $energy['current'] ?? 0;

// Calculate jump results
    $jumpResults = $this->calculateJumpResults($gymData, $totalStats, $maxHappy, $currentEnergy, $energy['maximum'] ?? 0);

        return view('jumps.index', [
            'error' => null,
            'bars' => $bars,
            'stats' => [
                'strength' => $strength,
                'defense' => $defense,
                'speed' => $speed,
                'dexterity' => $dexterity,
            ],
            'current_happy' => $currentHappy,
            'max_happy' => $maxHappy,
            'current_energy' => $energy['current'] ?? 0,
            'max_energy' => $energy['maximum'] ?? 0,
            'strength' => $strength,
            'defense' => $defense,
            'speed' => $speed,
            'dexterity' => $dexterity,
            'total_stats' => $totalStats,
            'gym_name' => $gymName,
            'gym_id' => $gymId,
            'gym_energy_cost' => $gymData['energy_cost'],
            'gym_dots_display' => $gymData['dots'],
            'gym_str_bonus' => $gymData['str_bonus'],
            'gym_def_bonus' => $gymData['def_bonus'],
            'gym_spd_bonus' => $gymData['spd_bonus'],
            'gym_dex_bonus' => $gymData['dex_bonus'],
            'gym_dots' => $gymData['dots'],
            'jump_results' => $jumpResults,
        ]);
    }

    private function getGymName(?int $gymId): string
    {
        if (!$gymId) return 'No Gym';
        
        $gymNames = [
            1 => 'Premier Fitness',
            2 => 'Average Joes',
            3 => "Woody's Workout",
            4 => 'Beach Bods',
            5 => 'Silver Gym',
            6 => 'Pour Femme',
            7 => 'Davies Den',
            8 => 'Global Gym',
            9 => 'Knuckle Heads',
            10 => 'Pioneer Fitness',
            11 => 'Anabolic Anomalies',
            12 => 'Core',
            13 => 'Racing Fitness',
            14 => 'Complete Cardio',
            15 => 'Legs, Bums and Tums',
            16 => 'Deep Burn',
            17 => 'Apollo Gym',
            18 => 'Gun Shop',
            19 => 'Force Training',
            20 => "Cha Cha's",
            21 => 'Atlas',
            22 => 'Last Round',
            23 => 'The Edge',
            24 => "George's",
            25 => 'Balboas Gym',
        ];
        
        return $gymNames[$gymId] ?? 'Unknown Gym';
    }

    private function getGymData(?int $gymId): array
    {
        // Gym data from Torn documentation
        // Energy: 5 for light-weight, 10 for middle/heavy, 25 for specialist
        // Dots: API value / 10 (e.g., 73 -> 7.3)
        // Stat bonuses: API value / 10 (e.g., 20 -> 2.0)
        $gymData = [
            // Light-Weight Gyms (5 energy)
            1  => ['energy_cost' => 5, 'dots' => 2.0, 'str_bonus' => 2.0, 'def_bonus' => 2.0, 'spd_bonus' => 2.0, 'dex_bonus' => 2.0],
            2  => ['energy_cost' => 5, 'dots' => 2.7, 'str_bonus' => 2.4, 'def_bonus' => 2.8, 'spd_bonus' => 2.4, 'dex_bonus' => 2.4],
            3  => ['energy_cost' => 5, 'dots' => 3.0, 'str_bonus' => 2.7, 'def_bonus' => 3.0, 'spd_bonus' => 3.2, 'dex_bonus' => 2.7],
            4  => ['energy_cost' => 5, 'dots' => 3.4, 'str_bonus' => 3.2, 'def_bonus' => 3.2, 'spd_bonus' => 3.2, 'dex_bonus' => 0],
            5  => ['energy_cost' => 5, 'dots' => 3.7, 'str_bonus' => 3.4, 'def_bonus' => 3.4, 'spd_bonus' => 3.6, 'dex_bonus' => 3.2],
            6  => ['energy_cost' => 5, 'dots' => 4.0, 'str_bonus' => 3.4, 'def_bonus' => 3.6, 'spd_bonus' => 3.6, 'dex_bonus' => 3.8],
            7  => ['energy_cost' => 5, 'dots' => 4.2, 'str_bonus' => 3.7, 'def_bonus' => 3.7, 'spd_bonus' => 0, 'dex_bonus' => 3.7],
            8  => ['energy_cost' => 5, 'dots' => 4.5, 'str_bonus' => 4.0, 'def_bonus' => 4.0, 'spd_bonus' => 4.0, 'dex_bonus' => 4.0],
            // Middle-Weight Gyms (10 energy)
            9  => ['energy_cost' => 10, 'dots' => 4.8, 'str_bonus' => 4.8, 'def_bonus' => 4.0, 'spd_bonus' => 4.4, 'dex_bonus' => 4.2],
            10 => ['energy_cost' => 10, 'dots' => 4.8, 'str_bonus' => 4.4, 'def_bonus' => 4.8, 'spd_bonus' => 4.6, 'dex_bonus' => 4.4],
            11 => ['energy_cost' => 10, 'dots' => 5.1, 'str_bonus' => 5.0, 'def_bonus' => 5.2, 'spd_bonus' => 4.6, 'dex_bonus' => 4.6],
            12 => ['energy_cost' => 10, 'dots' => 5.3, 'str_bonus' => 5.0, 'def_bonus' => 5.0, 'spd_bonus' => 5.2, 'dex_bonus' => 5.0],
            13 => ['energy_cost' => 10, 'dots' => 5.5, 'str_bonus' => 5.0, 'def_bonus' => 4.8, 'spd_bonus' => 5.4, 'dex_bonus' => 5.2],
            14 => ['energy_cost' => 10, 'dots' => 5.7, 'str_bonus' => 5.5, 'def_bonus' => 5.5, 'spd_bonus' => 5.7, 'dex_bonus' => 5.2],
            15 => ['energy_cost' => 10, 'dots' => 5.9, 'str_bonus' => 0, 'def_bonus' => 5.5, 'spd_bonus' => 5.5, 'dex_bonus' => 5.7],
            16 => ['energy_cost' => 10, 'dots' => 6.3, 'str_bonus' => 6.0, 'def_bonus' => 6.0, 'spd_bonus' => 6.0, 'dex_bonus' => 6.0],
            // Heavy-Weight Gyms (10 energy)
            17 => ['energy_cost' => 10, 'dots' => 6.5, 'str_bonus' => 6.0, 'def_bonus' => 6.4, 'spd_bonus' => 6.2, 'dex_bonus' => 6.2],
            18 => ['energy_cost' => 10, 'dots' => 6.7, 'str_bonus' => 6.5, 'def_bonus' => 6.2, 'spd_bonus' => 6.4, 'dex_bonus' => 6.2],
            19 => ['energy_cost' => 10, 'dots' => 6.9, 'str_bonus' => 6.4, 'def_bonus' => 6.4, 'spd_bonus' => 6.5, 'dex_bonus' => 6.8],
            20 => ['energy_cost' => 10, 'dots' => 7.0, 'str_bonus' => 6.4, 'def_bonus' => 6.8, 'spd_bonus' => 6.4, 'dex_bonus' => 7.0],
            21 => ['energy_cost' => 10, 'dots' => 7.1, 'str_bonus' => 7.0, 'def_bonus' => 6.4, 'spd_bonus' => 6.4, 'dex_bonus' => 6.5],
            22 => ['energy_cost' => 10, 'dots' => 7.2, 'str_bonus' => 6.8, 'def_bonus' => 7.0, 'spd_bonus' => 6.5, 'dex_bonus' => 6.5],
            23 => ['energy_cost' => 10, 'dots' => 7.3, 'str_bonus' => 6.8, 'def_bonus' => 7.0, 'spd_bonus' => 7.0, 'dex_bonus' => 6.8],
            24 => ['energy_cost' => 10, 'dots' => 7.3, 'str_bonus' => 7.3, 'def_bonus' => 7.3, 'spd_bonus' => 7.3, 'dex_bonus' => 7.3],
            // Specialist Gyms (25 energy)
            25 => ['energy_cost' => 25, 'dots' => 7.5, 'str_bonus' => 0, 'def_bonus' => 7.5, 'spd_bonus' => 0, 'dex_bonus' => 7.5],
        ];
        
        return $gymData[$gymId] ?? ['energy_cost' => 5, 'dots' => 2.0, 'str_bonus' => 2.0, 'def_bonus' => 2.0, 'spd_bonus' => 2.0, 'dex_bonus' => 2.0];
    }

private function calculateJumpResults(array $gymData, int $totalStats, int $maxHappy, int $currentEnergy, int $maxEnergy): array
    {
        // Vladar formula constants for each stat
        // A = (1-(H/99999)^2) * A constant
        // B = stat-specific offset
        $statConstants = [
            'strength' => ['A' => 1600, 'B' => 1700],
            'defense' => ['A' => 2100, 'B' => -600],
            'speed' => ['A' => 1600, 'B' => 2000],
            'dexterity' => ['A' => 1800, 'B' => 1500],
        ];

        // Material costs
        $xanaxCost = 810000; // $810,000
        $candyCost = 800; // $800
        $chocoCost = 50000; // $50,000
        $dvdCost = 4300000; // $4,300,000
        $ecstasyCost = 43000; // $43,000
        $refillPoints = 30; // 30 points

        // Xanax gives 250 energy
        $xanaxEnergy = 250;

        // Energy per train (from gym)
        $energyPerTrain = $gymData['energy_cost'];

        // Gym dots - use lowest non-zero stat bonus for conservative estimate
        $bonuses = array_filter([
            $gymData['str_bonus'],
            $gymData['def_bonus'],
            $gymData['spd_bonus'],
            $gymData['dex_bonus'],
        ]);
        $dots = !empty($bonuses) ? min($bonuses) : 2.0;

        // Happy loss per train (average from Vladar docs)
        // 5 energy: 2.67, 10 energy: 5, 25 energy: 12.67, 50 energy: 25
        $happyLossPerEnergy = 0.5; // 5/10 = 0.5
        
        // Jump types
        $jumpTypes = [
            'basic' => [
                'name' => 'Basic Training',
                'materials' => [],
                'total_energy' => $maxEnergy, // Uses max energy
                'happy_from_items' => 0,
                'has_ecstasy' => false,
                'has_refill' => false,
                'time_based' => false, // Not a jump, no xanax cooldown
            ],
            'xanax' => [
                'name' => 'Xanax Jump',
                'materials' => [
                    'xanax' => 4,
                    'refill' => 1,
                ],
                'total_energy' => 4 * $xanaxEnergy + 250, // 1000 + 250 from refill
                'happy_from_items' => 0,
                'has_ecstasy' => false,
                'has_refill' => true,
                'time_based' => true,
            ],
            'ecstasy' => [
                'name' => 'Ecstasy Jump',
                'materials' => [
                    'xanax' => 4,
                    'ecstasy' => 1,
                    'refill' => 1,
                ],
                'total_energy' => 4 * $xanaxEnergy + 250, // 1000 + 250 from refill
                'happy_from_items' => 0,
                'has_ecstasy' => true,
                'has_refill' => true,
                'time_based' => true,
            ],
            'candy' => [
                'name' => 'Candy Jump',
                'materials' => [
                    'xanax' => 4,
                    'candy' => 48,
                    'ecstasy' => 1,
                    'refill' => 1,
                ],
                'total_energy' => 4 * $xanaxEnergy, // 1000 energy from xanax
                'happy_from_items' => 48 * 25, // 1200 happy from candy (25 each)
                'has_ecstasy' => true,
                'has_refill' => true,
                'time_based' => true,
            ],
            'choco' => [
                'name' => 'Choco Jump',
                'materials' => [
                    'xanax' => 4,
                    'choco' => 48,
                    'ecstasy' => 1,
                    'refill' => 1,
                ],
                'total_energy' => 4 * $xanaxEnergy, // 1000 energy from xanax
                'happy_from_items' => 48 * 50, // 2400 happy from choco kisses (50 each)
                'has_ecstasy' => true,
                'has_refill' => true,
                'time_based' => true,
            ],
            'happy' => [
                'name' => 'Happy Jump',
                'materials' => [
                    'xanax' => 4,
                    'dvd' => 5,
                    'ecstasy' => 1,
                    'refill' => 1,
                ],
                'total_energy' => 4 * $xanaxEnergy, // 1000 energy from xanax
                'happy_from_items' => 5 * 2500, // 12500 happy from DVDs (2500 each)
                'has_ecstasy' => true,
                'has_refill' => true,
                'time_based' => true,
            ],
        ];

        $results = [];

        foreach ($jumpTypes as $type => $jump) {
            // Calculate total cost in money
            $moneyCost = 0;
            
            if (isset($jump['materials']['xanax'])) {
                $moneyCost += $jump['materials']['xanax'] * $xanaxCost;
            }
            if (isset($jump['materials']['ecstasy'])) {
                $moneyCost += $jump['materials']['ecstasy'] * $ecstasyCost;
            }
            if (isset($jump['materials']['candy'])) {
                $moneyCost += $jump['materials']['candy'] * $candyCost;
            }
            if (isset($jump['materials']['choco'])) {
                $moneyCost += $jump['materials']['choco'] * $chocoCost;
            }
            if (isset($jump['materials']['dvd'])) {
                $moneyCost += $jump['materials']['dvd'] * $dvdCost;
            }

            // Calculate points cost
            $pointsCost = isset($jump['materials']['refill']) ? $jump['materials']['refill'] * $refillPoints : 0;

            // Calculate total time - only for jumps (xanax cooldown)
            if ($jump['time_based'] ?? false) {
                $xanaxCount = $jump['materials']['xanax'] ?? 0;
                $totalTimeMin = $xanaxCount * 6;
                $totalTimeMax = $xanaxCount * 8;
            } else {
                // Basic training - no xanax cooldown
                $totalTimeMin = 0;
                $totalTimeMax = 0;
            }

            // Calculate happy after items
            $happyFromItems = $jump['happy_from_items'] ?? 0;
            
            // Starting happy is max happy + happy from items
            $startingHappy = $maxHappy + $happyFromItems;
            
            // Ecstasy doubles the happy
            if ($jump['has_ecstasy'] ?? false) {
                $startingHappy *= 2;
            }

            // Calculate available energy for training
            $availableEnergy = $jump['total_energy'];
            // Add refill energy if present in materials (already in total_energy for xanax/ecstasy, but not for candy/choco/happy)
            if (isset($jump['materials']['refill']) && $jump['materials']['refill'] > 0 && $type !== 'xanax' && $type !== 'ecstasy') {
                $availableEnergy += 250;
            }

            // Calculate number of trains
            $numTrains = floor($availableEnergy / $energyPerTrain);

            // Calculate estimated stat gain using Vladar formula
            $totalGain = 0;
            $currentJumpHappy = $startingHappy;
            
            // Calculate average A and B for overall estimate
            $avgA = ($statConstants['strength']['A'] + $statConstants['defense']['A'] + $statConstants['speed']['A'] + $statConstants['dexterity']['A']) / 4;
            $avgB = ($statConstants['strength']['B'] + $statConstants['defense']['B'] + $statConstants['speed']['B'] + $statConstants['dexterity']['B']) / 4;

            for ($i = 0; $i < $numTrains; $i++) {
                // Vladar formula: dS = (S * (1 + 0.07 * LN(1+H/250)) + 8 * H^1.05 + (1-(H/99999)^2) * A + B) * (1/200000) * G * E
                // S = stat total (capped at 50,000,000)
                $S = min($totalStats, 50000000);
                
                // S term
                $Sterm = $S * (1 + 0.07 * log(1 + $currentJumpHappy / 250));
                
                // Happy power term
                $happyTerm = 8 * pow($currentJumpHappy, 1.05);
                
                // Happy adjustment term
                $happyAdjTerm = (1 - pow($currentJumpHappy / 99999, 2)) * $avgA + $avgB;
                
                // Base calculation
                $baseGain = ($Sterm + $happyTerm + $happyAdjTerm) * (1 / 200000);
                
                // Apply gym dots (already in scale of 10) and energy
                $gainPerTrain = $baseGain * $dots * $energyPerTrain;
                
                $totalGain += $gainPerTrain;

                // Happy loss (average from Vladar docs)
                $happyLoss = round($energyPerTrain * 0.5);
                $currentJumpHappy = max(0, $currentJumpHappy - $happyLoss);
            }

            // Calculate price per train
            $pricePerTrain = $numTrains > 0 ? $moneyCost / $numTrains : 0;

            // Calculate points per train
            $pointsPerTrain = $numTrains > 0 ? $pointsCost / $numTrains : 0;

            // Build materials list with costs
            $materialsList = [];

            if (isset($jump['materials']['xanax']) && $jump['materials']['xanax'] > 0) {
                $materialsList[] = [
                    'name' => 'Xanax',
                    'qty' => $jump['materials']['xanax'],
                    'cost_each' => $xanaxCost,
                    'cost_total' => $jump['materials']['xanax'] * $xanaxCost,
                ];
            }

            if (isset($jump['materials']['candy'])) {
                $materialsList[] = [
                    'name' => 'Candy',
                    'qty' => $jump['materials']['candy'],
                    'cost_each' => $candyCost,
                    'cost_total' => $jump['materials']['candy'] * $candyCost,
                ];
            }

            if (isset($jump['materials']['choco'])) {
                $materialsList[] = [
                    'name' => 'Bag of Candy Kisses',
                    'qty' => $jump['materials']['choco'],
                    'cost_each' => $chocoCost,
                    'cost_total' => $jump['materials']['choco'] * $chocoCost,
                ];
            }

            if (isset($jump['materials']['dvd'])) {
                $materialsList[] = [
                    'name' => 'Erotic DVD',
                    'qty' => $jump['materials']['dvd'],
                    'cost_each' => $dvdCost,
                    'cost_total' => $jump['materials']['dvd'] * $dvdCost,
                ];
            }

            if (isset($jump['materials']['ecstasy']) && $jump['materials']['ecstasy'] > 0) {
                $materialsList[] = [
                    'name' => 'Ecstasy',
                    'qty' => $jump['materials']['ecstasy'],
                    'cost_each' => $ecstasyCost,
                    'cost_total' => $jump['materials']['ecstasy'] * $ecstasyCost,
                ];
            }

            if (isset($jump['materials']['refill']) && $jump['materials']['refill'] > 0) {
                $materialsList[] = [
                    'name' => 'Refill Energy Bar',
                    'qty' => $jump['materials']['refill'],
                    'cost_each' => '30 pts',
                    'cost_total' => $refillPoints . ' pts',
                ];
            }

            $results[$type] = [
                'name' => $jump['name'],
                'money_cost' => $moneyCost,
                'points_cost' => $pointsCost,
                'total_time_min' => $totalTimeMin,
                'total_time_max' => $totalTimeMax,
                'total_energy' => $availableEnergy,
                'num_trains' => $numTrains,
                'total_gain' => $totalGain,
                'gain_per_train' => $numTrains > 0 ? $totalGain / $numTrains : 0,
                'price_per_train' => $pricePerTrain,
                'points_per_train' => $pointsPerTrain,
                'starting_happy' => $startingHappy,
                'happy_from_items' => $happyFromItems,
                'materials_list' => $materialsList,
                'time_based' => $jump['time_based'] ?? false,
            ];
        }

        return $results;
    }
}
