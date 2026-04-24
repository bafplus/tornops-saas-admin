@extends('layouts.app')

@section('title', 'Dashboard - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <span class="text-gray-400">Faction: {{ $settings->faction_id ?? 'N/A' }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Total Members</h3>
            <p class="text-4xl font-bold text-blue-400">{{ $totalMembers }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Last Sync</h3>
            <p class="text-lg font-semibold text-green-400">
                {{ $settings->updated_at ? $settings->updated_at->diffForHumans() : 'Never' }}
            </p>
        </div>
    </div>

    @if(!empty($ocAlerts) || !$inactiveMembers->isEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if(!empty($ocAlerts))
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-orange-400">Organized Crimes Alerts</h2>
                <a href="/organized-crimes" class="text-orange-400 hover:text-orange-300 text-sm">View all →</a>
            </div>
            
            <div class="space-y-2">
                @foreach($ocAlerts as $alert)
                <div class="flex items-center gap-3 p-3 rounded-lg 
                    {{ $alert['severity'] === 'danger' ? 'bg-red-900/30 border border-red-700' : 
                       ($alert['severity'] === 'warning' ? 'bg-yellow-900/30 border border-yellow-700' : 
                       'bg-blue-900/30 border border-blue-700') }}">
                    @if($alert['severity'] === 'danger')
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    @elseif($alert['severity'] === 'warning')
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    @else
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    @endif
                    <span class="{{ $alert['severity'] === 'danger' ? 'text-red-400' : 
                                   ($alert['severity'] === 'warning' ? 'text-yellow-400' : 'text-blue-400') }}">
                        {{ $alert['message'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!$inactiveMembers->isEmpty())
        <div class="bg-gray-800 rounded-lg p-6 border border-red-900">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-red-400">Members Not in OC (14+ days)</h2>
                <span class="text-gray-400 text-sm">{{ $inactiveMembers->count() }} members</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-700">
                            <th class="pb-3">Member</th>
                            <th class="pb-3">Last OC</th>
                            <th class="pb-3">Days Ago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($inactiveMembers as $member)
                        <tr class="hover:bg-gray-700/50">
                            <td class="py-3">
                                <a href="https://www.torn.com/profiles.php?XID={{ $member->player_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                    {{ $member->name }}
                                </a>
                            </td>
                            <td class="py-3 text-gray-400">
                                @if($member->last_oc)
                                {{ \Carbon\Carbon::createFromTimestamp($member->last_oc)->format('M j, Y') }}
                                @else
                                <span class="text-red-400">Never</span>
                                @endif
                            </td>
                            <td class="py-3">
                                @if($member->last_oc)
                                @php $days = now()->diffInDays(\Carbon\Carbon::createFromTimestamp($member->last_oc)); @endphp
                                <span class="{{ $days > 20 ? 'text-red-400' : ($days > 14 ? 'text-yellow-400' : 'text-gray-400') }}">
                                    {{ $days }} days
                                </span>
                                @else
                                <span class="text-red-400 font-medium">N/A</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif
        
        @if($recentWars->isEmpty())
            <p class="text-gray-400">No wars found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-700">
                            <th class="pb-3">Opponent</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Score</th>
                            <th class="pb-3">Start (UTC)</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($recentWars as $war)
                        <tr class="hover:bg-gray-700/50">
                            <td class="py-4">{{ $war->opponent_faction_name ?? 'Unknown' }}</td>
                            <td class="py-4">
                                <span class="px-2 py-1 rounded text-xs 
                                    @if($war->status === 'won') bg-green-900 text-green-400
                                    @elseif($war->status === 'lost') bg-red-900 text-red-400
                                    @else bg-yellow-900 text-yellow-400
                                    @endif">
                                    {{ ucfirst($war->status) }}
                                </span>
                            </td>
                            <td class="py-4 font-mono">
                                {{ $war->score_ours ?? '-' }} - {{ $war->score_them ?? '-' }}
                            </td>
                            <td class="py-4 text-gray-400 font-mono text-sm">
                                {{ $war->start_date ? $war->start_date->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="py-4">
                                <a href="/wars/{{ $war->war_id }}" class="text-blue-400 hover:text-blue-300">Details →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if(!$inactiveMembers->isEmpty())
    <div class="bg-gray-800 rounded-lg p-6 border border-red-900">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-red-400">Members Not in OC (14+ days)</h2>
            <span class="text-gray-400 text-sm">{{ $inactiveMembers->count() }} members</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 border-b border-gray-700">
                        <th class="pb-3">Member</th>
                        <th class="pb-3">Last OC</th>
                        <th class="pb-3">Days Ago</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($inactiveMembers as $member)
                    <tr class="hover:bg-gray-700/50">
                        <td class="py-3">
                            <a href="https://www.torn.com/profiles.php?XID={{ $member->player_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                {{ $member->name }}
                            </a>
                        </td>
                        <td class="py-3 text-gray-400">
                            @if($member->last_oc)
                            {{ \Carbon\Carbon::createFromTimestamp($member->last_oc)->format('M j, Y') }}
                            @else
                            <span class="text-red-400">Never</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($member->last_oc)
                            @php $days = now()->diffInDays(\Carbon\Carbon::createFromTimestamp($member->last_oc)); @endphp
                            <span class="{{ $days > 20 ? 'text-red-400' : ($days > 14 ? 'text-yellow-400' : 'text-gray-400') }}">
                                {{ $days }} days
                            </span>
                            @else
                            <span class="text-red-400 font-medium">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
