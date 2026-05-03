@extends('layouts.app')

@section('title', 'Stocks - TornOps')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const thead = document.querySelector('thead');
    const tbody = document.querySelector('tbody');
    let sortCol = 'shares';
    let sortDir = 'desc';

    function updateSortIcons() {
        thead.querySelectorAll('th[data-sort]').forEach(h => {
            const icon = h.querySelector('.sort-icon');
            if (!icon) return;
            
            if (h.dataset.sort === sortCol) {
                h.dataset.dir = sortDir;
                icon.textContent = sortDir === 'asc' ? '↑' : '↓';
            } else {
                h.dataset.dir = h.dataset.sort === 'name' ? 'asc' : 'desc';
                icon.textContent = h.dataset.dir === 'asc' ? '↑' : '↓';
            }
        });
    }

    function sortTable() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let aVal = a.dataset[sortCol] || '0';
            let bVal = b.dataset[sortCol] || '0';
            
            if (['price', 'investors', 'id'].includes(sortCol)) {
                aVal = parseFloat(aVal.replace(/[^0-9.-]/g, '')) || 0;
                bVal = parseFloat(bVal.replace(/[^0-9.-]/g, '')) || 0;
                return sortDir === 'asc' ? aVal - bVal : bVal - aVal;
            }
            
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
            return sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    thead.addEventListener('click', function(e) {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        
        const col = th.dataset.sort;
        if (sortCol === col) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortCol = col;
            sortDir = 'desc';
        }
        
        updateSortIcons();
        sortTable();
    });

    // Initial sort
    updateSortIcons();
    sortTable();
});
</script>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Stock Market</h1>
            <p class="text-gray-400">Current market values from Torn</p>
        </div>
        <form action="/stocks/update" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh Data
            </button>
        </form>
    </div>

    <div class="mb-4 p-4 bg-gray-800 rounded-lg border border-gray-700">
        <button type="button" onclick="document.getElementById('stock-help').classList.toggle('hidden')" class="text-yellow-400 font-semibold hover:text-yellow-300 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            How Stock Benefits Work
        </button>
        <div id="stock-help" class="hidden mt-3 text-sm text-gray-300 space-y-2">
            <p><span class="text-green-400 font-semibold">Passive:</span> Need correct shares for 7 days before activating.</p>
            <p><span class="text-blue-400 font-semibold">Active:</span> Pay dividends every 7 or 31 days. Can buy multiple increments (each costs 2x previous).</p>
            <p><span class="text-yellow-400">Note:</span> Must claim dividends within 24h or lose progression. Excess dividends beyond Energy (1000) or Happiness (99999) limits will be wasted.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if($warActive ?? false)
    <div class="mb-4 p-4 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-400">
        <div class="flex items-center gap-2 font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Active War in Progress
        </div>
        <p class="text-sm mt-1">Non-essential API calls are disabled during active wars to conserve API usage. Stock data may be outdated. War-related syncs run every minute.</p>
    </div>
    @endif

    @if($error)
        <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ $error }}
        </div>
    @endif

    @if(!empty($userStocks))
    <div class="mb-6 bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-green-400">Your Portfolio</h2>
            <p class="text-gray-500 text-xs">Your current stock holdings</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr class="text-left text-gray-400 text-sm">
                        <th class="p-3">Stock</th>
                        <th class="p-3 text-right">Shares</th>
                        <th class="p-3 text-right">Avg Price</th>
                        <th class="p-3 text-right">Current</th>
                        <th class="p-3 text-right">Value</th>
                        <th class="p-3 text-right">P/L</th>
                        <th class="p-3">Bonus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($userStocks as $us)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-3">
                            <div class="font-medium">{{ $us['name'] }}</div>
                            <div class="text-gray-500 text-xs">{{ $us['acronym'] }}</div>
                        </td>
                        <td class="p-3 text-right font-mono">{{ number_format($us['shares']) }}</td>
                        <td class="p-3 text-right font-mono">${{ number_format($us['avg_price'], 2) }}</td>
                        <td class="p-3 text-right font-mono">${{ number_format($us['current_price'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-white">${{ number_format($us['value'], 2) }}</td>
                        <td class="p-3 text-right font-mono">
                            @if($us['profit_loss'] > 0)
                                <span class="text-green-400">+${{ number_format($us['profit_loss'], 2) }}<br><span class="text-xs">(+{{ number_format($us['profit_loss_pct'], 2) }}%)</span></span>
                            @elseif($us['profit_loss'] < 0)
                                <span class="text-red-400">${{ number_format($us['profit_loss'], 2) }}<br><span class="text-xs">({{ number_format($us['profit_loss_pct'], 2) }}%)</span></span>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="p-3 text-xs">
                            @if(isset($us['bonus']['available']) && $us['bonus']['available'])
                                <span class="text-green-400 bg-green-900/30 px-2 py-1 rounded">Ready!</span>
                            @elseif(isset($us['bonus']['progress']) && $us['bonus']['progress'])
                                <span class="text-yellow-400">{{ $us['bonus']['progress'] }}%</span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($recommendations && $recommendations->count() > 0)
    <div class="mb-6 bg-gray-800 rounded-lg border border-yellow-700/50 overflow-hidden">
        <div class="p-4 border-b border-gray-700 bg-yellow-900/20">
            <h2 class="text-xl font-semibold text-yellow-400">Investment Recommendations</h2>
            <p class="text-gray-400 text-xs">Best passive income stocks ranked by ROI</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700/50">
                    <tr class="text-left text-gray-400 text-sm">
                        <th class="p-3">#</th>
                        <th class="p-3">Stock</th>
                        <th class="p-3 text-right">Price</th>
                        <th class="p-3 text-right">Shares Needed</th>
                        <th class="p-3 text-right">Cost to Unlock</th>
                        <th class="p-3 text-right">Payout</th>
                        <th class="p-3 text-right">ROI %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($recommendations as $i => $rec)
                    <tr class="hover:bg-gray-700/30 @if($i === 0) bg-green-900/10 @endif">
                        <td class="p-3 font-mono text-gray-500">{{ $i + 1 }}</td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                @if($i === 0)<span class="text-green-400 font-bold">★</span>@endif
                                <div>
                                    <div class="font-medium">{{ $rec['name'] }}</div>
                                    <div class="text-gray-500 text-xs">{{ $rec['acronym'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 text-right font-mono">${{ number_format($rec['price'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-gray-400">{{ number_format($rec['shares_needed']) }}</td>
                        <td class="p-3 text-right font-mono text-yellow-400">${{ number_format($rec['cost_to_unlock'], 0) }}</td>
                        <td class="p-3 text-right text-xs">
                            <span class="text-green-400 font-medium">{{ $rec['payout'] }}</span>
                            <div class="text-gray-500 text-xs">${{ number_format($rec['payout_amount'], 0) }} value</div>
                        </td>
                        <td class="p-3 text-right font-bold">
                            <span class="@if($rec['roi_percent'] >= 50) text-green-400 @elseif($rec['roi_percent'] >= 20) text-yellow-400 @else text-gray-400 @endif">
                                {{ number_format($rec['roi_percent'], 1) }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(empty($stocks))
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 text-center">
            <p class="text-gray-400">No stock data available. Click refresh to load.</p>
        </div>
    @else
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3 cursor-pointer hover:text-white" data-sort="id" data-dir="asc">ID <span class="sort-icon">↑</span></th>
                            <th class="p-3 cursor-pointer hover:text-white" data-sort="name" data-dir="asc">Stock <span class="sort-icon">↑</span></th>
                            <th class="p-3 text-right cursor-pointer hover:text-white" data-sort="price" data-dir="desc">Price <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right cursor-pointer hover:text-white" data-sort="change_24h" data-dir="desc">24h / 48h <span class="sort-icon">↓</span></th>
                            <th class="p-3">24h Chart</th>
                            <th class="p-3">Bonus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($stocks as $stock)
                        <tr class="hover:bg-gray-700/30" data-id="{{ $stock['id'] }}" data-name="{{ strtolower($stock['name']) }}" data-price="{{ $stock['price'] }}" data-change_24h="{{ $stock['change_24h'] ?? 0 }}" data-change_48h="{{ $stock['change_48h'] ?? 0 }}" data-shares="{{ $stock['shares'] ?? 0 }}">
                            <td class="p-3 font-mono text-gray-400">{{ $stock['id'] }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    @if($stock['logo'])
                                        <img src="{{ $stock['logo'] }}" class="w-6 h-6" alt="">
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $stock['name'] }}</div>
                                        <div class="text-gray-500 text-sm">{{ $stock['acronym'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-right font-mono">
                                <div>${{ number_format($stock['price'], 2) }}</div>
                                @if($stock['change_24h'] !== null)
                                    <div class="@if($stock['change_24h'] > 0) text-green-400 @elseif($stock['change_24h'] < 0) text-red-400 @else text-gray-500 @endif text-xs flex items-center justify-end gap-1">
                                        @if($stock['change_24h'] > 0)<span>▲</span>@elseif($stock['change_24h'] < 0)<span>▼</span>@endif
                                        {{ $stock['change_24h'] > 0 ? '+' : '' }}{{ number_format($stock['change_24h'], 2) }}% (24h)
                                    </div>
                                @endif
                            </td>
                            <td class="p-3 text-xs">
                                <div class="space-y-1 text-left">
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-500 w-6">24h</span>
                                        @if($stock['price_24h_ago'] !== null)
                                            <span class="font-mono">${{ number_format($stock['price_24h_ago'], 2) }}</span>
                                            @if($stock['change_24h'] > 0)<span class="text-green-400">▲</span>@elseif($stock['change_24h'] < 0)<span class="text-red-400">▼</span>@endif
                                        @else
                                            <span class="text-gray-600">-</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-500 w-6">48h</span>
                                        @if($stock['price_48h_ago'] !== null)
                                            <span class="font-mono">${{ number_format($stock['price_48h_ago'], 2) }}</span>
                                            @if($stock['change_48h'] > 0)<span class="text-green-400">▲</span>@elseif($stock['change_48h'] < 0)<span class="text-red-400">▼</span>@endif
                                        @else
                                            <span class="text-gray-600">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                @if(isset($history[$stock['id']]) && $history[$stock['id']]->count() > 1)
                                    @php
                                        $stockHistory = $history[$stock['id']]->sortBy('recorded_at');
                                        $prices = $stockHistory->pluck('price')->toArray();
                                        $min = min($prices);
                                        $max = max($prices);
                                        $range = $max - $min ?: 1;
                                        $firstPrice = $prices[0];
                                        $lastPrice = end($prices);
                                        $chartChange = $firstPrice > 0 ? (($lastPrice - $firstPrice) / $firstPrice * 100) : 0;
                                        $w = 90;
                                        $h = 30;
                                        $pad = 4;
                                        $points = [];
                                        $count = count($prices);
                                        foreach ($prices as $i => $price) {
                                            $x = $pad + ($i / ($count - 1)) * ($w - 2 * $pad);
                                            $y = $pad + ($h - 2 * $pad) - (($price - $min) / $range * ($h - 2 * $pad));
                                            $points[] = round($x, 1) . ',' . round($y, 1);
                                        }
                                        $lineColor = $chartChange > 0 ? '#4ade80' : ($chartChange < 0 ? '#f87171' : '#6b7280');
                                        $fillColor = $chartChange > 0 ? 'rgba(74,222,128,0.3)' : ($chartChange < 0 ? 'rgba(248,113,113,0.3)' : 'rgba(107,114,128,0.3)');
                                        $lastPoint = end($points);
                                        $lastCoords = explode(',', $lastPoint);
                                    @endphp
                                    <svg width="{{ $w }}" height="{{ $h }}" class="block">
                                        <polygon fill="{{ $fillColor }}" points="{{ $pad }},{{ $h - $pad }} {{ implode(' ', $points) }} {{ $w - $pad }},{{ $h - $pad }}"/>
                                        <polyline fill="none" stroke="{{ $lineColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="{{ implode(' ', $points) }}"/>
                                        <circle cx="{{ $lastCoords[0] }}" cy="{{ $lastCoords[1] }}" r="3" fill="{{ $lineColor }}"/>
                                    </svg>
                                @else
                                    <span class="text-gray-600">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-xs">
                                @if(is_array($stock['bonus_text']))
                                    <div class="@if($stock['bonus_text']['type'] === 'Passive') text-green-400 bg-green-900/30 @else text-blue-400 bg-blue-900/30 @endif px-2 py-1 rounded">
                                        <div class="font-semibold">{{ $stock['bonus_text']['type'] }}</div>
                                        <div class="text-gray-300">Own {{ $stock['bonus_text']['shares_needed'] }} → {{ $stock['bonus_text']['payout'] }}</div>
                                        <div class="text-gray-400 text-xs">{{ $stock['bonus_text']['frequency'] }}</div>
                                    </div>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($history && $history->count() > 0)
        <div class="mt-6 bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-300">Price History (24 hours)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-400">
                            <th class="p-2">Stock</th>
                            @foreach($history->first()->sortBy('created_at') as $day)
                                <th class="p-2 text-right">@if($day->created_at){{ $day->created_at->format('H:i') }}@endif</th>
                            @endforeach
                            <th class="p-2 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($history as $stockId => $records)
                            @php
                                $sorted = $records->sortBy('created_at');
                                $first = $sorted->first();
                                $last = $sorted->last();
                                $change = $first->price > 0 ? (($last->price - $first->price) / $first->price * 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-700/30">
                                <td class="p-2">
                                    <span class="font-medium">{{ $first->name }}</span>
                                    <span class="text-gray-500 text-xs ml-1">({{ $first->acronym }})</span>
                                </td>
                                @foreach($sorted as $day)
                                    <td class="p-2 text-right font-mono ${{
                                        $day->price > ($sorted->where('created_at', '<', $day->created_at)->last()->price ?? $day->price)
                                            ? 'text-green-400' : 'text-gray-400'
                                    }}">
                                        ${{ number_format($day->price, 2) }}
                                    </td>
                                @endforeach
                                <td class="p-2 text-right">
                                    @if($change > 0)
                                        <span class="text-green-400">+{{ number_format($change, 2) }}%</span>
                                    @elseif($change < 0)
                                        <span class="text-red-400">{{ number_format($change, 2) }}%</span>
                                    @else
                                        <span class="text-gray-500">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif
</div>
@endsection
