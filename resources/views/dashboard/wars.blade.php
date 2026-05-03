@extends('layouts.app')

@section('title', 'Ranked Wars - TornOps')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Ranked Wars</h1>
    </div>

    @if($activeWars->isNotEmpty())
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-purple-400">Active Wars</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-4">Opponent</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Start (UTC)</th>
                        <th class="p-4">End (UTC)</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($activeWars as $war)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">
                            <span class="font-semibold">{{ $war->opponent_faction_name ?? 'Unknown' }}</span>
                            <span class="text-gray-500 text-sm ml-2">#{{ $war->opponent_faction_id }}</span>
                        </td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded text-xs bg-yellow-900 text-yellow-400">
                                {{ ucfirst($war->status) }}
                            </span>
                        </td>
                        <td class="p-4 font-mono text-lg">
                            <span class="text-green-400">{{ $war->score_ours ?? 0 }}</span>
                            <span class="text-gray-500 mx-1">-</span>
                            <span class="text-red-400">{{ $war->score_them ?? 0 }}</span>
                        </td>
                        <td class="p-4 text-gray-400">
                            {{ $war->start_date ? $war->start_date->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="p-4 text-gray-400">
                            {{ $war->end_date ? $war->end_date->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="p-4">
                            <a href="/wars/{{ $war->war_id }}" class="text-blue-400 hover:text-blue-300">Details →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-gray-300">Past Wars</h2>
        </div>
        
        @if($pastWars->isEmpty())
            <div class="p-8 text-center text-gray-400">
                No past wars found.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-4">Opponent</th>
                        <th class="p-4">Result</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Start Date (UTC)</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($pastWars as $war)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">
                            <span class="font-semibold">{{ $war->opponent_faction_name ?? 'Unknown' }}</span>
                        </td>
                        <td class="p-4">
                            @if($war->status === 'won')
                                <span class="px-2 py-1 rounded text-xs bg-green-900 text-green-400">Won</span>
                            @elseif($war->status === 'lost')
                                <span class="px-2 py-1 rounded text-xs bg-red-900 text-red-400">Lost</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">{{ ucfirst($war->status) }}</span>
                            @endif
                        </td>
                        <td class="p-4 font-mono">
                            {{ $war->score_ours ?? '-' }} - {{ $war->score_them ?? '-' }}
                        </td>
                        <td class="p-4 text-gray-400">
                            {{ $war->start_date ? $war->start_date->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="p-4">
                            <a href="/wars/{{ $war->war_id }}" class="text-blue-400 hover:text-blue-300">Details →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
