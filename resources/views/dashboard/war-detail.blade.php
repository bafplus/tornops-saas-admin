@extends('layouts.app')

@section('title', 'War Details - TornOps')

@section('content')
<style>
@media (max-width: 768px) {
    .two-cols, .three-cols { grid-template-columns: 1fr !important; }
}
</style>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/wars" class="text-gray-400 hover:text-white text-sm mb-2 inline-block">← Back to Wars</a>
            <h1 class="text-3xl font-bold">{{ $war->opponent_faction_name ?? 'Unknown Faction' }}</h1>
            <span class="text-gray-400">War #{{ $war->war_id }}</span>
        </div>
        <div class="flex items-center gap-3">
            @if($war->status === 'won')
                <span class="px-4 py-2 rounded bg-green-900 text-green-400 font-semibold">Won</span>
            @elseif($war->status === 'lost')
                <span class="px-4 py-2 rounded bg-red-900 text-red-400 font-semibold">Lost</span>
            @else
                <span class="px-4 py-2 rounded bg-yellow-900 text-yellow-400 font-semibold">{{ ucfirst($war->status) }}</span>
            @endif
            <button onclick="location.reload()" class="px-3 py-2 rounded bg-blue-800 text-blue-300 hover:bg-blue-700">Refresh</button>
        </div>
    </div>

    @php
    $target = $war->data['war']['target'] ?? 1900;
    $ours = $war->score_ours ?? 0;
    $them = $war->score_them ?? 0;
    $diff = $ours - $them;
    $remaining = max(0, $target - $diff);
    $percent = $diff >= 0 ? round(($diff / $target) * 100, 1) : 0;
    $width = min(100, max(0, $percent));
    @endphp

    <div class="grid gap-6 three-cols" style="display: grid; grid-template-columns: repeat(3, 1fr);">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Start Date (UTC)</h3>
            <p class="text-xl font-mono">{{ $war->start_date ? $war->start_date->format('d M Y H:i') : 'Unknown' }}</p>
            <p class="text-2xl font-bold font-mono mt-2" id="timer-display"></p>
            <p class="text-gray-400 text-sm" id="timer-label"></p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-4">War Progress</h3>
            <div class="text-center">
                <div class="flex items-center justify-center space-x-6">
                    <div>
                        <p class="text-4xl font-bold text-green-400" id="score-ours">{{ $ours }}</p>
                        <p class="text-gray-400 mt-1 text-sm">{{ $settings->faction_name ?? 'Our Faction' }}</p>
                    </div>
                    <div class="text-2xl text-gray-500">-</div>
                    <div>
                        <p class="text-4xl font-bold text-red-400" id="score-them">{{ $them }}</p>
                        <p class="text-gray-400 mt-1 text-sm">{{ $war->opponent_faction_name ?? 'Opponent' }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-xs text-gray-400 mb-1">
                    <span>Target: {{ $target }} pts</span>
                    <span id="progress-percent">{{ $percent }}%</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-3">
                    <div class="{{ $diff >= 0 ? 'bg-green-500' : 'bg-yellow-500' }} h-3 rounded-full transition-all duration-300" style="width: {{ $width }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span class="{{ $diff >= 0 ? 'text-green-400' : 'text-yellow-400' }}">{{ $diff >= 0 ? '+' . $diff : $diff }} lead</span>
                    <span>{{ $remaining }} to win</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="mb-6">
                <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">End Date (UTC)</h3>
                <p class="text-xl font-mono">{{ $war->end_date ? $war->end_date->format('d M Y H:i') : 'Ongoing' }}</p>
            </div>
            <div>
                <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">War Duration</h3>
                <p class="text-2xl font-bold font-mono" id="duration-display"></p>
            </div>
        </div>
    </div>

    @php $hasRetaliations = $retaliationTargets->isNotEmpty(); @endphp
    <div class="grid gap-6 two-cols" style="display: grid; grid-template-columns: 1fr 1fr;">
        <div class="bg-gray-800 rounded-lg p-4" style="border: 1px solid {{ $hasRetaliations ? '#ea580c' : '#374151' }}">
            <h3 class="text-sm uppercase tracking-wide mb-1 flex items-center gap-2" style="color: {{ $hasRetaliations ? '#f97316' : '#9ca3af' }}">
                Retaliation Targets
            </h3>
            <p class="text-gray-500 text-xs mb-3">Click target to attack. Please HOSPITALIZE target to get bonus</p>
            <div class="space-y-2">
                @forelse($retaliationTargets as $target)
                <div class="flex items-center justify-between bg-orange-900/20 rounded p-3 border border-orange-800/50" data-retaliation-expires="{{ $target['expires_at']->timestamp }}">
                    <div>
                        <a href="https://www.torn.com/loader.php?sid=attack&user2ID={{ $target['target_id'] }}" target="_blank" class="font-medium text-orange-300 hover:text-orange-200">{{ $target['target_name'] }}</a>
                        <span class="text-gray-400 text-sm ml-2">hit {{ $target['attacked_by'] }}</span>
                    </div>
                    <div class="font-mono text-orange-400 text-lg retaliation-timer"></div>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No active retaliation targets</div>
                @endforelse
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <h3 class="text-sm uppercase tracking-wide mb-2 flex items-center gap-2 text-purple-400">
                Chaining
            </h3>
            @php
            $checkpoints = [10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000];
            function renderChain($chainData, $color, $borderColor, $textColor, $checkpoints) {
                if (!$chainData) return '<div class="bg-gray-700/30 rounded p-2 mb-2 text-center text-gray-500 text-sm">No active chain</div>';
                $level = $chainData['level'];
                $maxSegments = 25;
                $nextCheckpoint = 10;
                foreach ($checkpoints as $cp) {
                    if ($level < $cp) { $nextCheckpoint = $cp; break; }
                    $nextCheckpoint = $cp;
                }
                $denominator = 10;
                foreach ($checkpoints as $cp) {
                    if ($level <= $cp) { $denominator = $cp; break; }
                    $denominator = $cp;
                }
                $prevCheckpoint = 0;
                foreach ($checkpoints as $cp) {
                    if ($level <= $cp) break;
                    $prevCheckpoint = $cp;
                }
                $filled = min($level - $prevCheckpoint, 25);
                if ($filled <= 0) $filled = $level;
                $prefix = $prevCheckpoint > 0 ? ($prevCheckpoint . '+ ') : '';
                $nextText = $level >= 100000 ? 'MAX CHAIN' : 'Next: ' . $nextCheckpoint . ' (' . ($nextCheckpoint - $level) . ' to go)';
                $bgClass = $color === 'green' ? 'from-green-900/40 to-green-800/20' : 'from-red-900/40 to-red-800/20';
                $borderClass = 'border-' . $color . '-500';
                $html = '<div class="bg-gradient-to-r ' . $bgClass . ' rounded p-3 mb-2 ' . $borderClass . '" data-chain-expires="' . $chainData['expires_at']->timestamp . '">';
                $html .= '<div class="text-xs ' . $textColor . ' mb-1">' . e($chainData['faction_name']) . '</div>';
                $html .= '<div class="flex items-center mb-1">';
                $html .= '<span class="text-' . $color . '-400 font-bold text-sm mr-2">' . $prefix . '</span>';
                $html .= '<div class="flex gap-0.5" style="min-width: ' . ($maxSegments * 14) . 'px;">';
                for ($i = 1; $i <= $maxSegments; $i++) {
                    $html .= '<div class="w-3 h-3 rounded-sm ' . ($i <= $filled ? 'bg-' . $color . '-400' : 'bg-gray-600') . '"></div>';
                }
                $html .= '</div>';
                $html .= '<span class="text-' . $color . '-400 font-bold text-lg ml-2">' . $level . '</span>';
                $html .= '<span class="text-' . $color . '-400 font-bold text-sm">/' . $denominator . '</span>';
                $html .= '<div class="flex-1"></div>';
                $html .= '<div class="font-mono text-' . $color . '-300 text-lg ml-2 chain-timer"></div>';
                $html .= '</div>';
                $html .= '<div class="flex justify-between text-xs text-gray-400">';
                $html .= '<div class="flex gap-3">';
                $html .= '<span>H: ' . $chainData['level'] . '</span>';
                $html .= '<span>Max: ' . $chainData['max_chain'] . '</span>';
                $html .= '</div>';
                $html .= '<span>' . $nextText . '</span>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            }
            @endphp
            @if($activeChain || $oppActiveChain)
                <div class="space-y-1">
                    {!! renderChain($activeChain, 'green', 'green', 'text-green-400', $checkpoints) !!}
                    {!! renderChain($oppActiveChain, 'red', 'red', 'text-red-400', $checkpoints) !!}
                </div>
            @else
                <div class="bg-gray-700/30 rounded p-2 text-center text-gray-500 text-sm">
                    No active chains
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 two-cols" style="display: grid; grid-template-columns: 1fr 1fr;">
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-green-400" id="header-our">{{ $settings->faction_name ?? 'Our Faction' }} (<span id="pts-our">{{ $war->score_ours ?? 0 }}</span> pts)</h2>
<p class="text-gray-500 text-xs mt-1">War score based on hits on opponent members from attack logs</p>

            
        </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-700 cursor-pointer select-none" id="thead-our">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3" data-sort="name" data-dir="asc">Name <span class="sort-icon">↑</span></th>
                            <th class="p-3" data-sort="level" data-dir="desc">Level <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="ff" data-dir="desc">FF <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="stats" data-dir="desc">Stats <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="hits" data-dir="desc">Hits <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="pwar" data-dir="desc">Score <span class="sort-icon">↓</span></th>
                            <th class="p-3" data-sort="status" data-dir="asc">Status <span class="sort-icon">↑</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-our">
                        @foreach($ourMembers as $member)
                        @php
                            $stats = $attackStats[$member->player_id] ?? null;
                            $hits = $stats->successful ?? 0;
                            $successful = $stats->successful ?? 0;
                            $failed = $stats->failed ?? 0;
                            $interrupted = $stats->interrupted ?? 0;
                            $warScore = $stats->total_score ?? 0;
                            $data = $member->data ?? [];
                            $statusData = $data['status'] ?? [];
                            $until = $statusData['until'] ?? 0;
                            $remaining = $until > 0 ? max(0, $until - time()) : 0;
                            $onlineStatus = $member->online_status ?? '';
                            $statusColor = $member->status_color ?? '';
                            $statusType = match($statusColor) {
                                'green' => '0',
                                'red' => '1',
                                default => '2'
                            };
                        @endphp
                        <tr class="hover:bg-gray-700/30" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-hits="{{ $hits }}" data-pwar="{{ $warScore }}" data-status="{{ $member->status_description ?? '' }}" data-status-type="{{ $statusType }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2 @if($member->online_status === 'Online') bg-green-500 @elseif($member->online_status === 'Idle') bg-yellow-500 @else bg-gray-500 @endif"></span>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                            </td>
                            <td class="p-3">{{ $member->level }}</td>
                            <td class="p-3 text-right font-mono text-green-400">{{ $member->ff_score ?? '-' }}</td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</span>
                                @if($member->ff_updated_at)
                                    <span class="block text-[10px] text-gray-600">{{ $member->ff_updated_at->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-blue-400 text-lg">{{ $hits }}</span>
                                <span class="block text-xs text-gray-500">S:{{ $successful }} F:{{ $failed }} I:{{ $interrupted }}</span>
                            </td>
                            <td class="p-3 text-right font-mono text-purple-400">{{ $warScore > 0 ? number_format($warScore, 2) : '-' }}</td>
                            <td class="p-3">
                                @if($member->status_color === 'red')
                                    @php 
                                    $data = $member->data;
                                    $statusData = $data['status'] ?? [];
                                    $until = $statusData['until'] ?? 0;
                                    $remaining = $until > 0 ? max(0, $until - time()) : 0;
                                    $h = floor($remaining / 3600);
                                    $m = floor(($remaining % 3600) / 60);
                                    $s = $remaining % 60;
                                    $timeStr = $remaining > 0 ? ($h > 0 ? "{$h}h {$m}m" : ($m > 0 ? "{$m}m {$s}s" : "{$s}s")) : '';
                                    $statusDesc = $statusData['description'] ?? $member->status_description ?? 'Hospital';
                                    if (stripos($statusDesc, 'In hospital for') === 0) {
                                        $statusDesc = 'In Hospital';
                                    }
                                    @endphp
                                    @if($until > 0 && $remaining > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer" data-until="{{ $until }}"><span class="hospital-time">{{ $statusDesc }} ({{ $timeStr }})</span></span>
                                    @elseif($until > 0 && $remaining <= 0)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium">Released</span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium">{{ $statusDesc }}</span>
                                    @endif
                                @elseif($member->status_color === 'blue')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium travel-bubble" data-status-changed="{{ $member->travel_started_at?->timestamp ?? $member->status_changed_at?->timestamp }}" data-travel-method="{{ !empty($member->property_name) && $member->property_name === 'Private Island' ? 'airstrip' : 'standard' }}">
                                        <span class="torn-icon" style="display:none;width:12px;height:12px;border:1px solid currentColor;border-radius:50%;text-align:center;line-height:10px;font-size:8px;">T</span>
                                        <svg class="w-3 h-3 plane-icon" style="display:none;" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                                        <span class="travel-text">{{ $member->status_description ?? 'Traveling' }}</span><span class="travel-eta ml-1 font-mono"></span>
                                        @if(!empty($member->property_name))
                                        <span class="ml-1 text-[10px] opacity-75">- {{ $member->property_name === 'Private Island' ? 'Airstrip' : 'Standard' }}</span>
                                        @endif
                                    </span>
                                @elseif($member->status_color === 'green')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium">{{ $member->status_description ?? 'Okay' }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-700/50 text-gray-400 text-xs font-medium">{{ $member->status_description ?? 'Offline' }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-red-400" id="header-opp">{{ $war->opponent_faction_name ?? 'Opponent' }} (<span id="pts-opp">{{ $war->score_them ?? 0 }}</span> pts)</h2>
                <p class="text-gray-500 text-xs mt-1">Click opponent name to attack</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-700 cursor-pointer select-none" id="thead-opp">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3" data-sort="name" data-dir="asc">Name <span class="sort-icon">↑</span></th>
                            <th class="p-3" data-sort="level" data-dir="desc">Level <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="ff" data-dir="desc">FF <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right" data-sort="stats" data-dir="desc">Stats <span class="sort-icon">↓</span></th>
                            <th class="p-3" data-sort="status" data-dir="asc">Status <span class="sort-icon">↑</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-opp">
                        @foreach($opponentMembers as $member)
                        @php
                            $stats = $attackStats[$member->player_id] ?? null;
                            $hits = $stats->successful ?? 0;
                            $successful = $stats->successful ?? 0;
                            $failed = $stats->failed ?? 0;
                            $interrupted = $stats->interrupted ?? 0;
                            $leavingSoon = false;
                            $remaining = 0;
                            $onlineStatus = $member->online_status ?? '';
                            $statusColor = $member->status_color ?? '';
                            if ($statusColor === 'red' && isset($member->data['status']['until'])) {
                                $until = $member->data['status']['until'];
                                $remaining = $until > 0 ? max(0, $until - time()) : 0;
                                $leavingSoon = $until > 0 && ($until - time()) <= 300;
                            }
                            $isHospitalized = $statusColor === 'red';
                            $statusType = match(true) {
                                $onlineStatus === 'Online' => '0',
                                $isHospitalized => '1',
                                default => '2'
                            };
                        @endphp
                        <tr class="hover:bg-gray-700/30 {{ $leavingSoon ? 'bg-red-900/20' : '' }}" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-status="{{ $member->status_description ?? '' }}" data-status-type="{{ $statusType }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2 @if($member->online_status === 'Online') bg-green-500 @elseif($member->online_status === 'Idle') bg-yellow-500 @else bg-gray-500 @endif"></span>
                                <a href="https://www.torn.com/loader.php?sid=attack&user2ID={{ $member->player_id }}" target="_blank" class="font-medium hover:text-blue-400">{{ $member->name }}</a>
                                @if(in_array($member->player_id, $topTargetIds ?? []))
                                    @php $rank = array_search($member->player_id, $topTargetIds) + 1; @endphp
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold
                                        @if($rank === 1) bg-yellow-500 text-black
                                        @elseif($rank === 2) bg-gray-400 text-black
                                        @else bg-amber-700 text-white @endif">
                                        #{{ $rank }}
                                    </span>
                                @endif
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                            </td>
                            <td class="p-3">{{ $member->level }}</td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-red-400">{{ $member->ff_score ?? '-' }}</span>
                                @if($member->ff_score)
                                    @php $difficulty = match(true) {
                                        $member->ff_score <= 1 => 'Extremely easy',
                                        $member->ff_score <= 2 => 'Easy',
                                        $member->ff_score <= 3.5 => 'Moderate',
                                        $member->ff_score <= 4.5 => 'Difficult',
                                        default => 'Impossible',
                                    }; @endphp
                                    <span class="block text-[10px] @if($difficulty === 'Extremely easy' || $difficulty === 'Easy') text-green-400 @elseif($difficulty === 'Moderate') text-yellow-400 @elseif($difficulty === 'Difficult') text-orange-400 @else text-red-400 @endif">{{ $difficulty }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</span>
                                @if($member->ff_updated_at)
                                    <span class="block text-[10px] text-gray-600">{{ $member->ff_updated_at->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if($member->status_color === 'red')
                                    @php 
                                    $data = $member->data;
                                    $statusData = $data['status'] ?? [];
                                    $until = $statusData['until'] ?? 0;
                                    $remaining = $until > 0 ? max(0, $until - time()) : 0;
                                    $h = floor($remaining / 3600);
                                    $m = floor(($remaining % 3600) / 60);
                                    $s = $remaining % 60;
                                    $timeStr = $remaining > 0 ? ($h > 0 ? "{$h}h {$m}m" : ($m > 0 ? "{$m}m {$s}s" : "{$s}s")) : '';
                                    $statusDesc = $statusData['description'] ?? $member->status_description ?? 'Hospital';
                                    if (stripos($statusDesc, 'In hospital for') === 0) {
                                        $statusDesc = 'In Hospital';
                                    }
                                    @endphp
                                    @if($until > 0 && $remaining > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer" data-until="{{ $until }}"><span class="hospital-time">{{ $statusDesc }} ({{ $timeStr }})</span></span>
                                    @elseif($until > 0 && $remaining <= 0)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium">Released</span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium">{{ $statusDesc }}</span>
                                    @endif
                                @elseif($member->status_color === 'blue')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium travel-bubble" data-status-changed="{{ $member->travel_started_at?->timestamp ?? $member->status_changed_at?->timestamp }}" data-travel-method="{{ !empty($member->property_name) && $member->property_name === 'Private Island' ? 'airstrip' : 'standard' }}">
                                        <span class="torn-icon" style="display:none;width:12px;height:12px;border:1px solid currentColor;border-radius:50%;text-align:center;line-height:10px;font-size:8px;">T</span>
                                        <svg class="w-3 h-3 plane-icon" style="display:none;" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                                        <span class="travel-text">{{ $member->status_description ?? 'Traveling' }}</span><span class="travel-eta ml-1 font-mono"></span>
                                        @if(!empty($member->property_name))
                                        <span class="ml-1 text-[10px] opacity-75">- {{ $member->property_name === 'Private Island' ? 'Airstrip' : 'Standard' }}</span>
                                        @endif
                                    </span>
                                @elseif($member->status_color === 'green')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium">{{ $member->status_description ?? 'Okay' }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-700/50 text-gray-400 text-xs font-medium">{{ $member->status_description ?? 'Offline' }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <button class="bell-btn text-lg" data-player="{{ $member->player_id }}">🔕</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold">War Attacks</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-4">Attacker</th>
                        <th class="p-4">Result</th>
                        <th class="p-4">Defender</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Date/Time (UTC)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($attacks as $attack)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">{{ $attack->attacker_name ?? 'Unknown' }}</td>
                        <td class="p-4">
                            @if($attack->result === 'Attacked' || $attack->result === 'Hospitalized')
                                <span class="px-2 py-1 rounded text-xs bg-green-900 text-green-400">{{ $attack->result }}</span>
                            @elseif($attack->result === 'Lost' || $attack->result === 'Stalemate')
                                <span class="px-2 py-1 rounded text-xs bg-red-900 text-red-400">{{ $attack->result }}</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">{{ $attack->result }}</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $attack->defender_name ?? 'Unknown' }}</td>
                        <td class="p-4 font-mono text-purple-400">{{ $attack->respect_gain > 0 ? '+' . $attack->respect_gain : '-' }}</td>
                        <td class="p-4 text-gray-400">{{ $attack->timestamp ? $attack->timestamp->format('d M H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td class="p-4 text-gray-500" colspan="5">No attacks recorded</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $attacks->links() }}

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div class="bg-gray-800 rounded-lg p-3">
                    <div class="text-gray-400 text-xs">Total Hits</div>
                    <div class="text-xl font-bold text-green-400">{{ $totalHits ?? 0 }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-3">
                    <div class="text-gray-400 text-xs">Top Hitter</div>
                    <div class="text-lg font-bold text-blue-400">{{ $topHitterName ?? 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ $topHitterHits ?? 0 }} hits</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-3">
                    <div class="text-gray-400 text-xs">Best Respect</div>
                    <div class="text-lg font-bold text-purple-400">{{ $topRespectName ?? 'N/A' }}</div>
                    <div class="text-xs text-gray-500">+{{ number_format($topRespectVal ?? 0, 2) }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-3">
                    <div class="text-gray-400 text-xs">Attackers</div>
                    <div class="text-xl font-bold text-yellow-400">{{ $attackStats->count() }}</div>
                </div>
            </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
'use strict';
var status = '{{ $war->status }}';
var startTimestamp = {{ $war->start_date ? $war->start_date->timestamp : 0 }};
var endTimestamp = {{ $war->end_date ? $war->end_date->timestamp : 0 }};
var warId = {{ $war->war_id }};
var isActiveWar = ['in progress', 'pending'].indexOf(status) !== -1;

function formatDuration(seconds) {
const d = Math.floor(seconds / 86400);
const h = Math.floor((seconds % 86400) / 3600);
const m = Math.floor((seconds % 3600) / 60);
const s = seconds % 60;

let result = '';
if (d > 0) result += d + 'd ';
if (h > 0 || d > 0) result += h + 'h ';
if (m > 0 || h > 0 || d > 0) result += m + 'm ';
result += s + 's';
return result;
}

function updateTimer() {
const now = Math.floor(Date.now() / 1000);
const label = document.getElementById('timer-label');
const display = document.getElementById('timer-display');
const durationDisplay = document.getElementById('duration-display');

if (status === 'pending') {
const diff = startTimestamp - now;
if (diff > 0) {
label.textContent = 'Starts in';
display.textContent = formatDuration(diff);
display.className = 'text-2xl font-bold font-mono mt-2 text-yellow-400';
} else {
label.textContent = 'Started';
display.textContent = formatDuration(Math.abs(diff)) + ' ago';
display.className = 'text-2xl font-bold font-mono mt-2 text-green-400';
}
} else if (status === 'in progress') {
const diff = now - startTimestamp;
label.textContent = 'In progress for';
display.textContent = formatDuration(diff);
display.className = 'text-2xl font-bold font-mono mt-2 text-green-400';
} else if (status === 'won' || status === 'lost') {
display.textContent = '';
label.textContent = '';
}

if ((status === 'won' || status === 'lost') && endTimestamp > 0 && startTimestamp > 0) {
const duration = endTimestamp - startTimestamp;
durationDisplay.textContent = formatDuration(duration);
} else if (status === 'in progress') {
durationDisplay.textContent = formatDuration(now - startTimestamp);
} else {
durationDisplay.textContent = '--';
}
}

function formatTime(seconds) {
var h = Math.floor(seconds / 3600);
var m = Math.floor((seconds % 3600) / 60);
var s = seconds % 60;
if (h > 0) {
return h + ':' + m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
}
return m + ':' + s.toString().padStart(2, '0');
}

function getHospitalCountry(title) {
var countryMap = {
'South African': 'SA', 'South Africa': 'SA',
'United Kingdom': 'UK', 'UK': 'UK',
'United Arab Emirates': 'UAE', 'UAE': 'UAE',
'United States': 'USA', 'USA': 'USA',
'Canada': 'CA', 'Cayman Islands': 'CA',
'Mexico': 'MX', 'Japan': 'JP',
'China': 'CN', 'Hong Kong': 'HK',
'Argentina': 'AR', 'Brazil': 'BR',
'Switzerland': 'CH', 'Germany': 'DE',
'France': 'FR', 'Italy': 'IT',
'Netherlands': 'NL', 'Spain': 'ES',
'Australia': 'AU', 'New Zealand': 'NZ',
'India': 'IN'
};
for (var full in countryMap) {
if (title && title.indexOf(full) !== -1) {
return countryMap[full];
}
}
return null;
}

function updateHospitalTimers() {
    var now = Math.floor(Date.now() / 1000);
    var elements = document.querySelectorAll('.hospital-timer');
    for (var i = 0; i < elements.length; i++) {
        var el = elements[i];
        var until = parseInt(el.getAttribute('data-until'));
        if (isNaN(until) || until <= 0) continue;

        var timeEl = el.querySelector('.hospital-time');
        if (!timeEl) continue;

        var remaining = until - now;
            if (remaining > 0) {
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;
            var timeStr = h > 0 ? (h + 'h ' + m + 'm') : (m > 0 ? (m + 'm ' + s + 's') : (s + 's'));
            var currentText = timeEl.textContent.replace(/\s*\([^)]*\)\s*$/, '').trim();
            if (currentText.toLowerCase().startsWith('in hospital for')) {
                currentText = 'In Hospital';
            }
            timeEl.textContent = currentText + ' (' + timeStr + ')';
        } else {
            timeEl.textContent = 'Released';
            el.classList.remove('bg-red-900/50', 'text-red-400');
            el.classList.add('bg-green-900/50', 'text-green-400');
        }
    }
}

function updateTravelTimers() {
    var now = Math.floor(Date.now() / 1000);
    var bubbles = document.querySelectorAll('.travel-bubble');
    for (var i = 0; i < bubbles.length; i++) {
        var bubble = bubbles[i];
        var travelStarted = parseInt(bubble.getAttribute('data-status-changed'));
        var travelTime = parseInt(bubble.getAttribute('data-travel-time')) || 60;
        var etaEl = bubble.querySelector('.travel-eta');

        if (!etaEl || isNaN(travelStarted)) {
            if (etaEl) etaEl.textContent = '';
            continue;
        }

        // Calculate ETA: travel_started + travel_time
        var travelTimeSec = travelTime * 60;
        var eta = travelStarted + travelTimeSec;
        var remaining = eta - now;

        if (remaining > 0) {
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;
            if (h > 0) {
                etaEl.textContent = 'ETA ' + h + ':' + m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
            } else {
                etaEl.textContent = 'ETA ' + m + ':' + s.toString().padStart(2, '0');
            }
        } else {
            etaEl.textContent = 'Arrived';
        }
    }
}

function updateRetaliationTimers() {
    var now = Math.floor(Date.now() / 1000);
    var timers = document.querySelectorAll('[data-retaliation-expires]');
    timers.forEach(function(row) {
        var expires = parseInt(row.getAttribute('data-retaliation-expires'));
        var timerEl = row.querySelector('.retaliation-timer');
        if (!timerEl) return;
        var remaining = expires - now;
        if (remaining > 0) {
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;
            timerEl.textContent = m + ':' + s.toString().padStart(2, '0');
        } else {
            timerEl.textContent = 'EXPIRED';
            row.classList.add('opacity-50');
        }
    });
}

function updateChainTimer() {
    var now = Math.floor(Date.now() / 1000);
    var chains = document.querySelectorAll('[data-chain-expires]');
    chains.forEach(function(chainRow) {
        var expires = parseInt(chainRow.getAttribute('data-chain-expires'));
        var timerEl = chainRow.querySelector('.chain-timer');
        if (!timerEl) return;
        var remaining = expires - now;
        if (remaining > 0) {
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;
            timerEl.textContent = m + ':' + s.toString().padStart(2, '0');
        } else {
            timerEl.textContent = 'EXPIRED';
        }
    });
}

function parseStats(str) {
if (!str) return 0;
const num = parseFloat(str);
if (str.includes('b')) return num * 1000000000;
if (str.includes('m')) return num * 1000000;
if (str.includes('k')) return num * 1000;
return num;
}

function sortTable(tbody, field, dir) {
const rows = Array.from(tbody.querySelectorAll('tr'));

rows.sort((a, b) => {
if (field === 'name') {
const aVal = a.dataset.name || '';
const bVal = b.dataset.name || '';
return dir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
} else if (field === 'level') {
const aVal = parseInt(a.dataset.level) || 0;
const bVal = parseInt(b.dataset.level) || 0;
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'ff') {
const aVal = parseFloat(a.dataset.ff) || 0;
const bVal = parseFloat(b.dataset.ff) || 0;
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'stats') {
const aVal = parseStats(a.dataset.stats);
const bVal = parseStats(b.dataset.stats);
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'pwar') {
const aVal = parseFloat(a.dataset.pwar) || 0;
const bVal = parseFloat(b.dataset.pwar) || 0;
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'hits') {
const aVal = parseInt(a.dataset.hits) || 0;
const bVal = parseInt(b.dataset.hits) || 0;
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'warscore') {
const aVal = parseFloat(a.dataset.warscore) || 0;
const bVal = parseFloat(b.dataset.warscore) || 0;
return dir === 'asc' ? aVal - bVal : bVal - aVal;
} else if (field === 'status') {
// Sort: green=0 first, red=1 second, others=2 last
const aScore = parseInt(a.dataset.statusType) || 2;
const bScore = parseInt(b.dataset.statusType) || 2;
if (aScore !== bScore) return dir === 'asc' ? aScore - bScore : bScore - aScore;
return 0;
}
return 0;
});

rows.forEach(row => tbody.appendChild(row));
}

function getUrlParams() {
const params = new URLSearchParams(window.location.search);
return {
sort: params.get('sort') || 'level',
dir: params.get('dir') || 'desc'
};
}

function updateUrlSort(sort, dir) {
const params = new URLSearchParams(window.location.search);
params.set('sort', sort);
params.set('dir', dir);
const newUrl = window.location.pathname + '?' + params.toString();
window.history.replaceState({path: newUrl}, '', newUrl);
}

function updateAllTheads(field, dir) {
['thead-our', 'thead-opp'].forEach(theadId => {
const thead = document.getElementById(theadId);
if (!thead) return;
thead.querySelectorAll('th').forEach(h => {
if (h.dataset.sort === field) {
h.dataset.dir = dir;
h.querySelector('.sort-icon').textContent = dir === 'asc' ? '↑' : '↓';
h.querySelector('.sort-icon').className = 'sort-icon';
} else {
h.dataset.dir = h.dataset.sort === 'name' || h.dataset.sort === 'status' ? 'asc' : 'desc';
h.querySelector('.sort-icon').textContent = h.dataset.dir === 'asc' ? '↑' : '↓';
h.querySelector('.sort-icon').className = 'sort-icon text-gray-500';
}
});
});
}

function handleSortClick(th) {
const field = th.dataset.sort;
const newDir = currentSort.field === field && currentSort.dir === 'desc' ? 'asc' : 'desc';

currentSort.field = field;
currentSort.dir = newDir;

updateAllTheads(field, newDir);
sortTable(document.getElementById('tbody-our'), field, newDir);
sortTable(document.getElementById('tbody-opp'), field, newDir);
updateUrlSort(currentSort.field, currentSort.dir);
}

function simplifyTravelStatus(original, travelMethod = 'standard') {
const countryMap = {
        'United Kingdom': 'UK',
        'United Arab Emirates': 'UAE',
        'UAE': 'UAE',
        'United States': 'USA',
        'USA': 'USA',
        'South Africa': 'SA',
        'New Zealand': 'NZ',
        'Hong Kong': 'HK',
        'Switzerland': 'CH',
        'Mexico': 'MX',
        'China': 'CN',
        'Japan': 'JP',
        'Argentina': 'AR',
        'Canada': 'CA',
        'Hawaii': 'Hawaii',
        'Cayman Islands': 'Cayman',
        'Cayman': 'Cayman',
'Germany': 'DE',
'France': 'FR',
'Italy': 'IT',
'Spain': 'ES',
'Netherlands': 'NL',
'Brazil': 'BR',
'India': 'IN',
'Australia': 'AU'
};

// Travel times in minutes - Airstrip (Private Island)
        const TRAVEL_TIME_AIRSTRIP = {
            'Mexico': 18, 'MX': 18,
            'Cayman Islands': 25, 'Cayman': 25,
            'Canada': 29, 'CA': 29,
            'Hawaii': 94,
            'United Kingdom': 111, 'UK': 111,
            'Argentina': 117, 'AR': 117,
            'Switzerland': 123, 'CH': 123,
            'Japan': 158, 'JP': 158,
            'China': 169, 'CN': 169,
            'UAE': 190,
            'South Africa': 208, 'SA': 208,
        };
        
        // Travel times in minutes - Standard (other properties)
        const TRAVEL_TIME_STANDARD = {
            'Mexico': 26, 'MX': 26,
            'Cayman Islands': 35, 'Cayman': 35,
            'Canada': 41, 'CA': 41,
            'Hawaii': 134,
            'United Kingdom': 159, 'UK': 159,
            'Argentina': 167, 'AR': 167,
            'Switzerland': 175, 'CH': 175,
            'Japan': 225, 'JP': 225,
            'China': 242, 'CN': 242,
            'UAE': 271,
            'South Africa': 297, 'SA': 297,
        };

let country = null;
    let displayName = null;
    for (const [full, short] of Object.entries(countryMap)) {
        if (original.includes(full)) {
            country = short;
            displayName = full;
            break;
        }
    }

    if (!country) {
        const match = original.match(/(?:In |Returning to Torn from |Traveling to )(.*)/);
        if (match) {
            country = match[1];
            displayName = match[1];
        }
    }

    const travelTimeTable = travelMethod === 'airstrip' ? TRAVEL_TIME_AIRSTRIP : TRAVEL_TIME_STANDARD;
        const travelTime = travelTimeTable[country] || 60;
    const finalDisplayName = displayName || country || original;

    if (original.startsWith('Returning to Torn from') && country) {
        return { direction: 'left', country: finalDisplayName, isTraveling: true, travelTime };
    }

    if (original.startsWith('Traveling to ') && country) {
        return { direction: 'right', country: finalDisplayName, isTraveling: true, travelTime };
    }

    if (original.startsWith('In ') && country) {
        return { direction: 'abroad', country: finalDisplayName, isTraveling: false };
    }

    return { direction: 'right', country: original, isTraveling: false };
}

function initTravelBubbles() {
            document.querySelectorAll('.travel-bubble').forEach(bubble => {
                var text = bubble.querySelector('.travel-text').textContent.trim();
                var travelMethod = bubble.dataset.travelMethod || 'standard';
                var result = simplifyTravelStatus(text, travelMethod);
                bubble.dataset.travelTime = result.travelTime;

var planeIcon = bubble.querySelector('.plane-icon');
var tornIcon = bubble.querySelector('.torn-icon');
var travelText = bubble.querySelector('.travel-text');

if (result.isTraveling) {
if (planeIcon) {
planeIcon.style.display = 'inline';
planeIcon.style.transform = result.direction === 'left' ? 'rotate(-90deg)' : 'rotate(90deg)';
}
if (tornIcon) tornIcon.style.display = 'inline';
travelText.textContent = result.country + ' ';
} else {
if (planeIcon) planeIcon.style.display = 'none';
if (tornIcon) tornIcon.style.display = 'none';
travelText.textContent = 'In ' + result.country + ' ';
}
});
}

const urlParams = getUrlParams();
let currentSort = { field: urlParams.sort, dir: urlParams.dir };

console.log('Script loaded, currentSort:', currentSort);

document.addEventListener('DOMContentLoaded', function() {
console.log('DOM loaded, setting up sort handlers');
try {
document.getElementById('thead-our').querySelectorAll('th').forEach(th => {
th.addEventListener('click', () => { console.log('Sort clicked:', th.dataset.sort); handleSortClick(th); });
});
document.getElementById('thead-opp').querySelectorAll('th').forEach(th => {
th.addEventListener('click', () => { console.log('Sort clicked:', th.dataset.sort); handleSortClick(th); });
});
} catch(e) { console.error('Setup error:', e); }

console.log('Initial sort:', currentSort);
updateAllTheads(currentSort.field, currentSort.dir);
// Don't call sortTable on load - preserve server-side order

updateTimer();
setInterval(updateTimer, 1000);

updateHospitalTimers();
setInterval(updateHospitalTimers, 1000);

initTravelBubbles();
        updateTravelTimers();
        setInterval(updateTravelTimers, 1000);

        updateRetaliationTimers();
        setInterval(updateRetaliationTimers, 1000);

        updateChainTimer();
        setInterval(updateChainTimer, 1000);
});

        if (isActiveWar) {
setInterval(function() {
location.reload();
}, 30000);

(function() {
var lastSyncKey = 'lastRealtimeSync_' + warId;
var syncInterval = 32000;

function playAlert() {
try {
var ctx = new (window.AudioContext || window.webkitAudioContext)();
var osc = ctx.createOscillator();
var gain = ctx.createGain();
osc.connect(gain);
gain.connect(ctx.destination);
osc.frequency.value = 880;
osc.type = 'sine';
gain.gain.setValueAtTime(0.3, ctx.currentTime);
gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
osc.start(ctx.currentTime);
osc.stop(ctx.currentTime + 0.3);
} catch (e) {}
}

function syncRealtime() {
var now = Date.now();
var lastSync = parseInt(localStorage.getItem(lastSyncKey) || '0', 10);
if (now - lastSync < 60000) {
return;
}
var lastAttackCount = parseInt(localStorage.getItem('lastAttackCount_' + warId) || '0', 10);
localStorage.setItem(lastSyncKey, now.toString());
fetch('/api/wars/' + warId + '/attacks')
.then(function(r) { return r.json(); })
.then(function(data) {
if (data.attacks && data.attacks.length > lastAttackCount && lastAttackCount > 0) {
playAlert();
}
localStorage.setItem('lastAttackCount_' + warId, data.attacks ? data.attacks.length : '0');
fetch('/api/wars/' + warId + '/live')
.catch(function() {});
})
.catch(function() {});
}

syncRealtime();
setInterval(syncRealtime, syncInterval);

function playSOS() {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    var dot = 0.1, dash = 0.3, gap = 0.1, letterGap = 0.2, wordGap = 0.4;
    var now = ctx.currentTime;
    function beep(duration) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.setValueAtTime(0.3, now + duration);
        gain.gain.linearRampToValueAtTime(0, now + duration + 0.01);
        osc.start(now);
        osc.stop(now + duration + 0.01);
    }
    beep(dot); now += dot + gap;
    beep(dot); now += dot + gap;
    beep(dot); now += dot + letterGap;
    beep(dash); now += dash + gap;
    beep(dash); now += dash + gap;
    beep(dash); now += dash + wordGap;
    beep(dot); now += dot + gap;
    beep(dot); now += dot + gap;
    beep(dot);
}

function getBellKey(type, playerId) {
    return 'bell_' + warId + '_' + playerId + '_' + type;
}

function initBells() {
    var isOppTable = document.getElementById('tbody-opp');
    if (!isOppTable) return;
    
    document.querySelectorAll('.bell-btn').forEach(function(btn) {
        btn.textContent = '🔕';
    });
    document.querySelectorAll('.bell-btn').forEach(function(btn) {
        var row = btn.closest('tr');
        var timerEl = row.querySelector('.hospital-timer, .travel-bubble');
        if (!timerEl) { btn.style.display = 'none'; return; }
        var type = timerEl.classList.contains('hospital-timer') ? 'hospital' : 'travel';
        var playerEl = row.querySelector('td:first-child');
        var playerLink = playerEl.querySelector('a');
        var playerId = playerLink ? playerLink.textContent.replace('#', '').trim() : 'unknown';
        if (btn.dataset.player) playerId = btn.dataset.player;
        btn.dataset.type = type;
        btn.dataset.player = playerId;
        
        var key = getBellKey(type, playerId);
        if (localStorage.getItem(key)) {
            btn.textContent = '🔔';
        } else {
            btn.textContent = '🔕';
        }
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!('Notification' in window) || Notification.permission === 'default') {
                Notification.requestPermission();
            }
            
            var key = getBellKey(type, playerId);
            var wasEnabled = localStorage.getItem(key);
            
            if (wasEnabled) {
                localStorage.removeItem(key);
                localStorage.removeItem(key + '_done');
                btn.textContent = '🔕';
            } else {
                var row = btn.closest('tr');
                var timerEl = row.querySelector('.hospital-timer, .travel-bubble');
                if (!timerEl) return;
                var until = type === 'hospital' 
                    ? parseInt(timerEl.dataset.until)
                    : parseInt(timerEl.dataset.statusChanged) + parseInt(timerEl.dataset.travelTime) * 60;
                
                if (until > Math.floor(Date.now() / 1000)) {
                    localStorage.setItem(key, until);
                    btn.textContent = '🔔';
                }
            }
        });
    });
}

function checkBells() {
    var now = Math.floor(Date.now() / 1000);
    var alert5 = now + 300;
    
    document.querySelectorAll('.bell-btn').forEach(function(btn) {
        if (btn.textContent !== '🔔') return;
        var type = btn.dataset.type;
        var playerId = btn.dataset.player;
        var timerEl = btn.closest('tr').querySelector('.hospital-timer, .travel-bubble');
        if (!timerEl) return;
        var until = type === 'hospital'
            ? parseInt(timerEl.dataset.until)
            : parseInt(timerEl.dataset.statusChanged) + parseInt(timerEl.dataset.travelTime) * 60;
        
        var key = getBellKey(type, playerId);
        var alertsDone = JSON.parse(localStorage.getItem(key + '_done') || '[]');
        
        if (!alertsDone.includes('5min') && until <= alert5 && until > now) {
            alertsDone.push('5min');
            localStorage.setItem(key + '_done', JSON.stringify(alertsDone));
            var playerRow = timerEl.closest('tr');
            var playerLink = playerRow.querySelector('td:first-child a');
            var playerName = playerLink ? playerLink.textContent : btn.dataset.player;
            new Notification('TornOps Alert', {
                body: type === 'hospital' ? playerName + ' will leave hospital in 5 min' : playerName + ' will return in 5 min',
                icon: '/favicon.ico'
            });
            playSOS();
        }
        
        if (!alertsDone.includes('now') && until <= now) {
            alertsDone.push('now');
            localStorage.setItem(key + '_done', JSON.stringify(alertsDone));
            var playerRow = timerEl.closest('tr');
            var playerLink = playerRow.querySelector('td:first-child a');
            var playerName = playerLink ? playerLink.textContent : btn.dataset.player;
            new Notification('TornOps Alert', {
                body: type === 'hospital' ? playerName + ' is out of hospital!' : playerName + ' has returned!',
                icon: '/favicon.ico'
            });
            playSOS();
            localStorage.removeItem(key);
            btn.textContent = '🔕';
        }
    });
}

initBells();
setInterval(checkBells, 10000);
})();
}
})();
</script>
@endpush
