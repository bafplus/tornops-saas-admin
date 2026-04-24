@extends('layouts.app')

@section('title', 'Jump Helper - TornOps')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Jump Helper</h1>
        <p class="text-gray-400">Happy jump calculator - calculate stat gains</p>
    </div>

    @if($error)
        <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ $error }}
        </div>
    @endif

    @if($stats && $bars)
    <!-- Gym Info -->
    <div class="mb-6 bg-gray-800 rounded-lg border border-purple-700/50 p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-900/50 rounded-lg flex items-center justify-center">
                    <span class="text-purple-400 font-bold">{{ $gym_id ?? '?' }}</span>
                </div>
                <div>
                    <div class="font-semibold text-purple-400 text-lg">{{ $gym_name }}</div>
                    <div class="text-gray-500 text-sm">Energy per train: {{ $gym_energy_cost }} | Dots: {{ number_format($gym_dots_display, 1) }}</div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div class="bg-red-900/30 rounded p-2">
                <div class="text-red-400 font-bold">{{ number_format($gym_str_bonus, 1) }}x</div>
                <div class="text-gray-500">STR</div>
            </div>
            <div class="bg-blue-900/30 rounded p-2">
                <div class="text-blue-400 font-bold">{{ number_format($gym_def_bonus, 1) }}x</div>
                <div class="text-gray-500">DEF</div>
            </div>
            <div class="bg-yellow-900/30 rounded p-2">
                <div class="text-yellow-400 font-bold">{{ number_format($gym_spd_bonus, 1) }}x</div>
                <div class="text-gray-500">SPD</div>
            </div>
            <div class="bg-green-900/30 rounded p-2">
                <div class="text-green-400 font-bold">{{ number_format($gym_dex_bonus, 1) }}x</div>
                <div class="text-gray-500">DEX</div>
            </div>
        </div>
    </div>

    <!-- Current Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-blue-400">Battle Stats</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-400">Strength</span>
                    <span class="font-mono">{{ number_format($strength) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Defense</span>
                    <span class="font-mono">{{ number_format($defense) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Speed</span>
                    <span class="font-mono">{{ number_format($speed) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Dexterity</span>
                    <span class="font-mono">{{ number_format($dexterity) }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-700 pt-2">
                    <span class="text-white font-semibold">Total Stats</span>
                    <span class="font-mono text-yellow-400">{{ number_format($total_stats) }}</span>
                </div>
            </div>
        </div>

        <!-- Current Bars -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-blue-400">Bars</h2>
            
            <div class="space-y-4">
<div>
            <div class="flex justify-between items-center mb-1">
                <span class="text-gray-400">Happy</span>
                <div class="text-right">
                    <span class="font-mono text-yellow-400">{{ number_format($current_happy) }}</span>
                    <span class="text-gray-500"> / {{ number_format($max_happy) }}</span>
                </div>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-3">
                <div class="bg-yellow-500 h-3 rounded-full transition-all" style="width: {{ min(100, $current_happy / $max_happy * 100) }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ floor($current_happy / 250) }} jumps available (250 each)</div>
        </div>

        <div>
            <div class="flex justify-between items-center mb-1">
                <span class="text-gray-400">Energy</span>
                <div class="text-right">
                    <span class="font-mono text-green-400">{{ number_format($current_energy) }}</span>
                    <span class="text-gray-500"> / {{ number_format($max_energy) }}</span>
                </div>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full transition-all" style="width: {{ min(100, $current_energy / $max_energy * 100) }}%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ floor($current_energy / $gym_energy_cost) }} gym hits available ({{ $gym_energy_cost }} each)</div>
        </div>
            </div>
        </div>
    </div>

    <!-- Jump Calculations -->
    @if(isset($jump_results) && count($jump_results) > 0)
    <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-purple-400">Jump Calculations</h2>
            <span class="text-xs text-gray-500 italic">* Estimates only - actual gains vary based on happy loss variance (40-60%) and cooldown timing</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($jump_results as $type => $result)
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                <h3 class="text-lg font-semibold mb-3 {{ $type === 'basic' ? 'text-gray-400' : ($type === 'xanax' ? 'text-cyan-400' : ($type === 'ecstasy' ? 'text-purple-400' : ($type === 'candy' ? 'text-green-400' : ($type === 'choco' ? 'text-yellow-400' : 'text-pink-400')))) }}">
                    {{ $result['name'] }}
                </h3>
                
                <!-- Materials List -->
                <div class="mb-4 text-xs">
                    <div class="text-gray-500 mb-2">Materials Needed:</div>
                    <div class="space-y-1 bg-gray-900/50 rounded p-2">
                        @foreach($result['materials_list'] as $material)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">{{ $material['qty'] }}x {{ $material['name'] }}</span>
                            <span class="font-mono {{ is_numeric($material['cost_total']) ? 'text-green-400' : 'text-blue-400' }}">
                                {{ is_numeric($material['cost_total']) ? '$' . number_format($material['cost_total']) : $material['cost_total'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Money Cost</span>
                        <span class="font-mono text-green-400">${{ number_format($result['money_cost']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Points Cost</span>
                        <span class="font-mono text-blue-400">{{ $result['points_cost'] }} pts</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Time</span>
                        <span class="font-mono">{{ isset($result['time_based']) && $result['time_based'] ? $result['total_time_min'] . '-' . $result['total_time_max'] . ' hrs' : 'Instant' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Energy</span>
                        <span class="font-mono">{{ number_format($result['total_energy']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Number of Trains</span>
                        <span class="font-mono">{{ $result['num_trains'] }}</span>
                    </div>
                    
<div class="border-t border-gray-700 pt-2 mt-2 bg-yellow-900/20 -mx-4 px-4 py-2 -mb-2 rounded-b-lg">
            <div class="text-center">
                <div class="text-gray-400 text-xs mb-1">Est. Total Gain</div>
                <div class="text-2xl font-bold font-mono text-yellow-400">{{ number_format($result['total_gain'], 0) }}</div>
            </div>
            <div class="flex justify-between mt-2">
                <span class="text-gray-500 text-xs">Gain per Train</span>
                <span class="font-mono text-gray-400 text-xs">{{ number_format($result['gain_per_train'], 2) }}</span>
            </div>
        </div>
                    
                    <div class="border-t border-gray-700 pt-2 mt-2">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Price per Train</span>
                            <span class="font-mono text-green-400">${{ number_format($result['price_per_train'], 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Points per Train</span>
                            <span class="font-mono text-blue-400">{{ number_format($result['points_per_train'], 2) }}</span>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-700 pt-2 mt-2">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Starting Happy</span>
                            <span class="font-mono text-green-400">{{ number_format($result['starting_happy']) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Happy from Items</span>
                            <span class="font-mono">+{{ number_format($result['happy_from_items']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
