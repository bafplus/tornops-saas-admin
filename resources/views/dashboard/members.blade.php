@extends('layouts.app')

@section('title', 'Faction Members - TornOps')

@section('content')
<div class="space-y-6">
    @if($warActive ?? false)
    <div class="p-4 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-400">
        <div class="flex items-center gap-2 font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Active War in Progress
        </div>
        <p class="text-sm mt-1">Member data syncing is disabled during active wars. Data may be outdated.</p>
    </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Faction Members</h1>
        <span class="text-gray-400">{{ $members->total() }} members</span>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50 text-sm cursor-pointer select-none" id="thead">
                        <th class="p-3" data-sort="name" data-dir="asc">Name <span class="sort-icon">↑</span></th>
                        <th class="p-3" data-sort="level" data-dir="desc">Level <span class="sort-icon">↓</span></th>
                        <th class="p-3 text-right" data-sort="ff" data-dir="desc">FF <span class="sort-icon">↓</span></th>
                        <th class="p-3 text-right" data-sort="stats" data-dir="desc">Stats <span class="sort-icon">↓</span></th>
                        <th class="p-3" data-sort="position" data-dir="asc">Position <span class="sort-icon">↑</span></th>
                        <th class="p-3 text-right" data-sort="days" data-dir="desc">Days <span class="sort-icon">↓</span></th>
                        <th class="p-3" data-sort="status" data-dir="asc">Status <span class="sort-icon">↑</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700" id="tbody">
                    @forelse($members as $member)
                    @php
                        $data = $member->data ?? [];
                        $statusData = $data['status'] ?? [];
                        $until = $statusData['until'] ?? 0;
                        $remaining = $until > 0 ? max(0, $until - time()) : 0;
                        $statsNum = 0;
                        if ($member->estimated_stats) {
                            $statsStr = strtolower($member->estimated_stats);
                            if (str_ends_with($statsStr, 'k')) {
                                $statsNum = floatval($statsStr) * 1000;
                            } elseif (str_ends_with($statsStr, 'm')) {
                                $statsNum = floatval($statsStr) * 1000000;
                            } else {
                                $statsNum = floatval($statsStr);
                            }
                        }
                        // Determine status type for sorting by color only: green=0, red=1, other=2
                        $statusColor = $member->status_color ?? '';
                        $statusType = match($statusColor) {
                            'green' => '0',
                            'red' => '1',
                            default => '2'
                        };
                    @endphp
                    <tr class="hover:bg-gray-700/30" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $statsNum }}" data-position="{{ strtolower($member->position ?? '') }}" data-days="{{ $member->days_in_faction ?? 0 }}" data-status="{{ $member->status_description ?? '' }}" data-status-type="{{ $statusType }}">
                        <td class="p-3">
                            <span class="inline-block w-2 h-2 rounded-full mr-2 @if($member->online_status === 'Online') bg-green-500 @elseif($member->online_status === 'Idle') bg-yellow-500 @else bg-gray-500 @endif"></span>
                            <a href="https://www.torn.com/profiles.php?XID={{ $member->player_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">{{ $member->name ?? 'Unknown' }}</a>
                            <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                            @if($member->revivable ?? false)
                            <span class="ml-1" title="Can be revived">🚨</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $member->level }}</td>
                        <td class="p-3 text-right font-mono text-green-400">{{ $member->ff_score ?? '-' }}</td>
                        <td class="p-3 text-right font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</td>
                        <td class="p-3 text-gray-300">{{ $member->position ?? $member->rank ?? '-' }}</td>
                        <td class="p-3 text-right text-gray-400">{{ $member->days_in_faction ?? '-' }}</td>
                        <td class="p-3">
                            @if($member->status_color === 'red')
                                @php
                                    $statusDesc = $statusData['description'] ?? $member->status_description ?? 'Hospital';
                                    if (stripos($statusDesc, 'In hospital for') === 0) {
                                        $statusDesc = 'In Hospital';
                                    }
                                @endphp
                                @if($until > 0 && $remaining > 0)
                                    @php
                                        $h = floor($remaining / 3600);
                                        $m = floor(($remaining % 3600) / 60);
                                        $timeStr = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer" data-until="{{ $until }}">
                                        <span class="hospital-time">{{ $statusDesc }} ({{ $timeStr }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium">{{ $statusDesc }}</span>
                                @endif
                            @elseif($member->status_color === 'blue')
<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium travel-bubble" data-status-changed="{{ $member->travel_started_at?->timestamp ?? $member->status_changed_at?->timestamp }}" data-travel-method="{{ !empty($member->property_name) && $member->property_name === 'Private Island' ? 'airstrip' : 'standard' }}">
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
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No members found. Sync faction data first.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($members->hasPages())
        <div class="p-4 border-t border-gray-700">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>

<script>
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

    document.addEventListener('DOMContentLoaded', function() {
    const thead = document.getElementById('thead');
    const tbody = document.getElementById('tbody');
    
    thead.addEventListener('click', function(e) {
        const th = e.target.closest('th');
        if (!th || !th.dataset.sort) return;
        
        const sortKey = th.dataset.sort;
        let dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
        
        // Reset all icons and set new direction for clicked column
        thead.querySelectorAll('th').forEach(h => {
            const isClicked = h.dataset.sort === sortKey;
            const newDir = isClicked ? dir : (h.dataset.sort === 'name' || h.dataset.sort === 'position' || h.dataset.sort === 'status' ? 'asc' : 'desc');
            h.dataset.dir = newDir;
            h.querySelector('.sort-icon').textContent = newDir === 'asc' ? '↑' : '↓';
        });
        
        // Sort rows
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            if (sortKey === 'status') {
                // Sort: green=0 first, red=1 second, others=2 last
                const aScore = parseInt(a.dataset['statusType']) || 2;
                const bScore = parseInt(b.dataset['statusType']) || 2;
                if (aScore !== bScore) return dir === 'asc' ? aScore - bScore : bScore - aScore;
                return 0;
            }
            
            if (['level', 'ff', 'stats', 'days'].includes(sortKey)) {
                const aVal = parseFloat(a.dataset[sortKey]) || 0;
                const bVal = parseFloat(b.dataset[sortKey]) || 0;
                return dir === 'asc' ? aVal - bVal : bVal - aVal;
            }
            
            const aVal = (a.dataset[sortKey] || '').toLowerCase();
            const bVal = (b.dataset[sortKey] || '').toLowerCase();
            return dir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    });
});

function updateTravelTimers() {
        var now = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.travel-bubble').forEach(function(bubble) {
            var etaEl = bubble.querySelector('.travel-eta');
            if (!etaEl) return;
            
            var statusChanged = parseInt(bubble.getAttribute('data-status-changed'));
            var travelMethod = bubble.getAttribute('data-travel-method') || 'standard';
            var text = bubble.querySelector('.travel-text').textContent.trim();
            
            // Extract destination from travel text
            var destination = null;
            var match = text.match(/(?:Traveling to |Returning to Torn from |In )(.*)/);
            if (match) {
                destination = match[1];
            }
            
            // Get travel time in minutes based on method and destination
            var travelTimeTable = travelMethod === 'airstrip' ? TRAVEL_TIME_AIRSTRIP : TRAVEL_TIME_STANDARD;
            var travelTime = travelTimeTable[destination] || 60;
            
            if (isNaN(statusChanged)) {
                etaEl.textContent = '';
                return;
            }
            
            var travelTimeSec = travelTime * 60;
        var earliestLanding = Math.floor(travelTimeSec / 1.03);
        var elapsed = now - statusChanged;
        var remaining = earliestLanding - elapsed;
        
        if (remaining > 0) {
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;
            if (h > 0) {
                etaEl.textContent = '(' + h + ':' + m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0') + ')';
            } else {
                etaEl.textContent = '(' + m + ':' + s.toString().padStart(2, '0') + ')';
            }
        } else {
            etaEl.textContent = '';
        }
    });
}

updateTravelTimers();
setInterval(updateTravelTimers, 1000);
</script>
@endsection
