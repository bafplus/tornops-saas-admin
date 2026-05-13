@extends('layouts.app')

@section('title', 'Bounties - TornOps')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Active Bounties</h1>
        <p class="text-gray-400">All active bounties across Torn, synced from the Torn API</p>
    </div>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-gray-400 text-sm">Total Bounties</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-gray-400 text-sm">Total Value</div>
            <div class="text-2xl font-bold text-yellow-400">${{ number_format($stats['total_value']) }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-gray-400 text-sm">Highest Bounty</div>
            <div class="text-2xl font-bold text-green-400">${{ number_format($stats['max_reward']) }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-gray-400 text-sm">Last Sync</div>
            <div class="text-lg font-bold">{{ $stats['last_sync'] ? $stats['last_sync']->diffForHumans() : 'Never' }}</div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 mb-6">
        <form method="GET" class="p-4 grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-gray-400 text-sm mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID"
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Min Reward</label>
                <input type="number" name="min_reward" value="{{ request('min_reward') }}" placeholder="$0"
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Max Reward</label>
                <input type="number" name="max_reward" value="{{ request('max_reward') }}" placeholder="Any"
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Min Level</label>
                <input type="number" name="min_level" value="{{ request('min_level') }}" placeholder="1"
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Max Level</label>
                <input type="number" name="max_level" value="{{ request('max_level') }}" placeholder="100"
                       class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white text-sm font-medium">Filter</button>
                <a href="/bounties" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded text-white text-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-700 text-gray-300 text-sm uppercase">
                    <th class="px-4 py-3 text-left">Target</th>
                    <th class="px-4 py-3 text-left">Level</th>
                    <th class="px-4 py-3 text-right">Reward</th>
                    <th class="px-4 py-3 text-left">Placed By</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Reason</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">Expires</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bounties as $bounty)
                <tr class="border-t border-gray-700 hover:bg-gray-750">
                    <td class="px-4 py-3">
                        <a href="https://www.torn.com/profiles.php?XID={{ $bounty->target_id }}"
                           target="_blank" class="text-blue-400 hover:text-blue-300">
                            {{ $bounty->target_name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-300">{{ $bounty->target_level }}</td>
                    <td class="px-4 py-3 text-right font-medium text-yellow-400">${{ number_format($bounty->reward) }}</td>
                    <td class="px-4 py-3">
                        @if($bounty->is_anonymous)
                            <span class="text-gray-500 italic">Anonymous</span>
                        @elseif($bounty->lister_id)
                            <a href="https://www.torn.com/profiles.php?XID={{ $bounty->lister_id }}"
                               target="_blank" class="text-blue-400 hover:text-blue-300">
                                {{ $bounty->lister_name }}
                            </a>
                        @else
                            <span class="text-gray-500 italic">Unknown</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-sm hidden md:table-cell max-w-xs truncate">
                        {{ $bounty->reason ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm hidden md:table-cell">
                        @php
                            $expires = \Carbon\Carbon::createFromTimestamp($bounty->valid_until);
                            $diff = now()->diffInHours($expires, false);
                        @endphp
                        @if($diff < 0)
                            <span class="text-red-400">Expired</span>
                        @elseif($diff < 24)
                            <span class="text-yellow-400">{{ $expires->diffForHumans() }}</span>
                        @else
                            <span class="text-gray-400">{{ $expires->format('M j, H:i') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No bounties match your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $bounties->links() }}
    </div>
</div>
@endsection
