@extends('layouts.app')

@section('title', 'Gym Assistant - TornOps')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Gym Assistant</h1>
            <p class="text-gray-400">Track your gym training progress over time</p>
        </div>
        <form action="/gym/update" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Update Stats
            </button>
        </form>
    </div>

    @if(session('success'))
        <div id="success-alert" class="mb-4 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div id="error-alert" class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ session('error') }}
        </div>
    @endif

    @if(isset($fetchError))
        <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ $fetchError }}
        </div>
    @endif

    @if(!Auth::user()->torn_api_key)
        <div class="mb-4 p-4 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-400">
            No API key found. Please add your Torn API key in <a href="/settings" class="underline">Settings</a> to fetch gym stats.
        </div>
    @endif

    @if($latestStats)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">Current Stats</h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Gym</span>
                    <span class="font-medium">{{ $latestStats->gym_name ?? 'Unknown' }}</span>
                </div>
                @php
                    $totalStats = ($latestStats->strength ?? 0) + ($latestStats->defense ?? 0) + ($latestStats->speed ?? 0) + ($latestStats->dexterity ?? 0);
                    $strPct = $totalStats > 0 ? round(($latestStats->strength ?? 0) / $totalStats * 100) : 0;
                    $defPct = $totalStats > 0 ? round(($latestStats->defense ?? 0) / $totalStats * 100) : 0;
                    $spdPct = $totalStats > 0 ? round(($latestStats->speed ?? 0) / $totalStats * 100) : 0;
                    $dexPct = $totalStats > 0 ? round(($latestStats->dexterity ?? 0) / $totalStats * 100) : 0;
                @endphp
                <div class="border-t border-gray-700 pt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-400">{{ number_format($latestStats->strength ?? 0) }} <span class="text-sm ml-1">{{ $strPct }}%</span></div>
                            <div class="text-sm text-gray-500">Strength</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-400">{{ number_format($latestStats->defense ?? 0) }} <span class="text-sm ml-1">{{ $defPct }}%</span></div>
                            <div class="text-sm text-gray-500">Defense</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-yellow-400">{{ number_format($latestStats->speed ?? 0) }} <span class="text-sm ml-1">{{ $spdPct }}%</span></div>
                            <div class="text-sm text-gray-500">Speed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-purple-400">{{ number_format($latestStats->dexterity ?? 0) }} <span class="text-sm ml-1">{{ $dexPct }}%</span></div>
                            <div class="text-sm text-gray-500">Dexterity</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">Total Battle Stats</h2>
            @php
                $total = ($latestStats->strength ?? 0) + ($latestStats->defense ?? 0) + ($latestStats->speed ?? 0) + ($latestStats->dexterity ?? 0);
            @endphp
            <div class="text-center py-8">
                <div class="text-4xl font-bold text-white mb-2">{{ number_format($total) }}</div>
                <div class="text-gray-500">Total</div>
            </div>
            <div class="mt-4">
                <div class="text-sm text-gray-400">Last updated: {{ $latestStats->recorded_at->format('d M Y H:i') }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">Training Program</h2>
            
            <form action="/gym/program" method="POST" class="space-y-4">
                @csrf
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm text-gray-400 mb-1">Select Program</label>
<select name="program_id" id="programSelect" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" onchange="toggleCustomInputs()">
                <option value="">-- Select --</option>
                @foreach($programs as $program)
                <option value="{{ $program->id }}" {{ $selectedProgramId == $program->id ? 'selected' : '' }}>
                    {{ $program->name }}{{ !$program->is_custom ? ' (' . $program->str_percent . '/' . $program->def_percent . '/' . $program->spd_percent . '/' . $program->dex_percent . ')' : '' }}
                </option>
                @endforeach
            </select>
            @if($selectedProgramId)
            <div class="text-sm text-green-400 mt-1">Current: {{ $programs->firstWhere('id', $selectedProgramId)?->name ?? 'Custom' }}</div>
            @endif
                    </div>
                    
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                        Save
                    </button>
                </div>
                
                <div id="customInputs" class="flex gap-2 {{ ($programId ?? 0) > 11 ? '' : 'hidden' }} mt-3">
                    <div class="w-20">
                        <label class="block text-xs text-gray-400 mb-1">STR %</label>
                        <input type="number" name="custom_str" value="{{ $percentages['str'] ?? 25 }}" min="0" max="100" class="w-full px-2 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs text-gray-400 mb-1">DEF %</label>
                        <input type="number" name="custom_def" value="{{ $percentages['def'] ?? 25 }}" min="0" max="100" class="w-full px-2 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs text-gray-400 mb-1">SPD %</label>
                        <input type="number" name="custom_spd" value="{{ $percentages['spd'] ?? 25 }}" min="0" max="100" class="w-full px-2 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs text-gray-400 mb-1">DEX %</label>
                        <input type="number" name="custom_dex" value="{{ $percentages['dex'] ?? 25 }}" min="0" max="100" class="w-full px-2 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                    </div>
                </div>
                
                @if($percentages)
                <div class="mt-4 p-3 bg-gray-700/50 rounded-lg">
                    <div class="flex gap-4 text-sm">
                        <span class="text-blue-400">STR: {{ $percentages['str'] }}%</span>
                        <span class="text-green-400">DEF: {{ $percentages['def'] }}%</span>
                        <span class="text-yellow-400">SPD: {{ $percentages['spd'] }}%</span>
                        <span class="text-purple-400">DEX: {{ $percentages['dex'] }}%</span>
                    </div>
                </div>
                @endif
            </form>
        </div>

        @if($trainRecommendation)
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">Train Recommendation</h2>
            
            <form method="GET" action="/gym" class="mb-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm text-gray-400">Training at:</label>
                    <select name="gym_id" class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" onchange="this.form.submit()">
                        @foreach($gyms as $gym)
                            <option value="{{ $gym['id'] }}" {{ $trainRecommendation['gym_id'] == $gym['id'] ? 'selected' : '' }}>
                                {{ $gym['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @if($latestStats && $latestStats->gym_name)
                        <span class="text-xs text-gray-500">(Current: {{ $latestStats->gym_name }})</span>
                    @endif
                </div>
            </form>

            <div class="mb-4">
                <div class="text-xs text-gray-500">Gym gains: STR +{{ $trainRecommendation['gym_gains']['strength'] }}, DEF +{{ $trainRecommendation['gym_gains']['defense'] }}, SPD +{{ $trainRecommendation['gym_gains']['speed'] }}, DEX +{{ $trainRecommendation['gym_gains']['dexterity'] }}</div>
            </div>
            
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-gray-700/50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-400">{{ $trainRecommendation['trains']['str'] }}</div>
            <div class="text-sm text-gray-400">STR trains</div>
        </div>
        <div class="bg-gray-700/50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-400">{{ $trainRecommendation['trains']['def'] }}</div>
            <div class="text-sm text-gray-400">DEF trains</div>
        </div>
        <div class="bg-gray-700/50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-400">{{ $trainRecommendation['trains']['spd'] }}</div>
            <div class="text-sm text-gray-400">SPD trains</div>
        </div>
        <div class="bg-gray-700/50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-400">{{ $trainRecommendation['trains']['dex'] }}</div>
            <div class="text-sm text-gray-400">DEX trains</div>
        </div>
    </div>
            
            <div class="text-center p-4 bg-blue-900/30 rounded-lg">
                <div class="text-lg font-bold text-white">Total: {{ $trainRecommendation['trains']['total'] }} trains</div>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($chartData && $chartData->count() > 0)
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">Progress Chart</h2>
            <div class="h-80">
                <canvas id="progressChart"></canvas>
            </div>
        </div>
        @endif

        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-300">History</h2>
            @if($history->count() > 0)
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                            <th class="pb-3">Date</th>
                            <th class="pb-3 text-right">STR</th>
                            <th class="pb-3 text-right">DEF</th>
                            <th class="pb-3 text-right">SPD</th>
                            <th class="pb-3 text-right">DEX</th>
                            <th class="pb-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($history as $record)
                        @php
                            $rowTotal = $record->strength + $record->defense + $record->speed + $record->dexterity;
                        @endphp
                        <tr class="hover:bg-gray-700/30">
                            <td class="py-2 text-gray-400 text-sm">{{ $record->recorded_at->format('d M H:i') }}</td>
                            <td class="py-2 text-right font-mono text-blue-400 text-sm">{{ number_format($record->strength) }}</td>
                            <td class="py-2 text-right font-mono text-green-400 text-sm">{{ number_format($record->defense) }}</td>
                            <td class="py-2 text-right font-mono text-yellow-400 text-sm">{{ number_format($record->speed) }}</td>
                            <td class="py-2 text-right font-mono text-purple-400 text-sm">{{ number_format($record->dexterity) }}</td>
                            <td class="py-2 text-right font-mono text-white text-sm">{{ number_format($rowTotal) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $history->links() }}
            </div>
            @else
            <p class="text-gray-500 text-center py-8">No history yet.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
console.log('Chart.js loaded:', typeof Chart);
console.log('Chart exists:', Chart !== undefined);

@if($chartData && $chartData->count() > 0)
var chartData = {
    labels: {!! json_encode($chartData->pluck('recorded_at')->map(fn($d) => $d->format('d M H:i'))) !!},
    strength: {!! json_encode($chartData->pluck('strength')) !!},
    defense: {!! json_encode($chartData->pluck('defense')) !!},
    speed: {!! json_encode($chartData->pluck('speed')) !!},
    dexterity: {!! json_encode($chartData->pluck('dexterity')) !!},
    total: {!! json_encode($chartData->map(fn($r) => $r->strength + $r->defense + $r->speed + $r->dexterity)) !!}
};

document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Total', data: chartData.total, borderColor: '#ffffff', backgroundColor: 'rgba(255, 255, 255, 0.1)', fill: false, tension: 0.3, borderWidth: 3 },
                { label: 'Strength', data: chartData.strength, borderColor: '#60a5fa', backgroundColor: 'rgba(96, 165, 250, 0.1)', fill: true, tension: 0.3 },
                { label: 'Defense', data: chartData.defense, borderColor: '#4ade80', backgroundColor: 'rgba(74, 222, 128, 0.1)', fill: true, tension: 0.3 },
                { label: 'Speed', data: chartData.speed, borderColor: '#facc15', backgroundColor: 'rgba(250, 204, 21, 0.1)', fill: true, tension: 0.3 },
                { label: 'Dexterity', data: chartData.dexterity, borderColor: '#c084fc', backgroundColor: 'rgba(192, 132, 252, 0.1)', fill: true, tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#9ca3af' } } },
            scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
            }
        }
    });
});
@endif

function toggleCustomInputs() {
    var select = document.getElementById('programSelect');
    var customInputs = document.getElementById('customInputs');
    var selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.text.includes('Custom')) {
        customInputs.classList.remove('hidden');
    } else {
        customInputs.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var successAlert = document.getElementById('success-alert');
        var errorAlert = document.getElementById('error-alert');
        if (successAlert) {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(function() { successAlert.remove(); }, 500);
        }
        if (errorAlert) {
            errorAlert.style.transition = 'opacity 0.5s';
            errorAlert.style.opacity = '0';
            setTimeout(function() { errorAlert.remove(); }, 500);
        }
    }, 4000);
});
</script>
@endpush
@endsection