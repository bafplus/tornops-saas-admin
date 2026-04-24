@extends('layouts.app')

@section('title', 'Organized Crimes - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Organized Crimes</h1>
        <span class="text-gray-400">{{ $ocs->count() }} OCs</span>
    </div>

    @forelse($ocs as $oc)
    <div class="bg-gray-800 rounded-lg overflow-hidden border 
        @if(in_array($oc->status, ['planning', 'Pending'])) border-yellow-600
        @elseif(in_array($oc->status, ['recruiting', 'Ready'])) border-blue-600
        @elseif(in_array($oc->status, ['success', 'Successful', 'successful', 'executed'])) border-green-600
        @elseif(in_array($oc->status, ['failure', 'Failed', 'failed'])) border-red-600
        @else border-gray-700 @endif">
        <div class="p-4 border-b border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">
                    <a href="https://www.torn.com/factions.php?step=your&type=1#/tab=crimes&crimeId={{ $oc->oc_id }}" target="_blank" class="hover:text-blue-400">
                        {{ $oc->name }}
                    </a>
                </h2>
                <div class="flex items-center gap-3 mt-1">
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if($oc->difficulty <= 3) bg-green-900/50 text-green-400
                        @elseif($oc->difficulty <= 5) bg-yellow-900/50 text-yellow-400
                        @elseif($oc->difficulty <= 7) bg-orange-900/50 text-orange-400
                        @else bg-red-900/50 text-red-400 @endif">
                        <span class="text-base font-bold">{{ $oc->difficulty }}</span><span class="text-xs">/10</span>
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        @if(in_array($oc->status, ['ready', 'Ready'])) bg-green-900/50 text-green-400
                        @elseif(in_array($oc->status, ['recruiting', 'Recruiting'])) bg-blue-900/50 text-blue-400
                        @elseif(in_array($oc->status, ['planning', 'Pending'])) bg-yellow-900/50 text-yellow-400
                        @elseif(in_array($oc->status, ['success', 'Successful', 'successful', 'executed'])) bg-green-900/50 text-green-400
                        @elseif(in_array($oc->status, ['failure', 'Failed', 'failed'])) bg-red-900/50 text-red-400
                        @else bg-gray-700 text-gray-400 @endif">
                        {{ ucfirst($oc->status) }}
                    </span>
                    @if($oc->ready_at && $oc->status !== 'executed')
                    <span class="text-gray-400 text-sm">
                        Ready: {{ $oc->ready_at ? \Carbon\Carbon::createFromTimestamp($oc->ready_at)->format('M j, H:i') : 'N/A' }}
                    </span>
                    @endif
                    @if($oc->rewards && in_array($oc->status, ['successful', 'Successful']))
                    <span class="text-green-400 text-sm ml-2">
                        💰 {{ number_format($oc->rewards['money'] ?? 0) }} | 
                        Respect +{{ $oc->rewards['respect'] ?? 0 }}
                        @if(($oc->rewards['payout']['percentage'] ?? 0) > 0)
                            | {{ $oc->rewards['payout']['percentage'] }}% paid
                        @endif
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4">
            <table class="min-w-full">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-300 uppercase">Position</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-300 uppercase">Member</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-300 uppercase">Progress</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-300 uppercase">CPR</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-300 uppercase">Item Required</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($oc->slots as $slot)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-3 py-2 text-sm">
                            {{ $slot->position }} #{{ $slot->position_number }}
                        </td>
                        <td class="px-3 py-2 text-sm">
                            @if($slot->user_id && $slot->member_name)
                            <a href="https://www.torn.com/profiles.php?XID={{ $slot->user_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                {{ $slot->member_name }}
                            </a>
                            @if($slot->user_outcome)
                            <span class="px-1.5 py-0.5 rounded text-xs ml-1 {{ $slot->user_outcome === 'Successful' ? 'bg-green-900/50 text-green-400' : 'bg-red-900/50 text-red-400' }}">
                                {{ $slot->user_outcome }}
                            </span>
                            @endif
                            @else
                            <span class="text-yellow-400 font-medium">OPEN</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm">
                            @if($slot->user_progress !== null)
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $slot->user_progress >= 70 ? 'bg-green-500' : ($slot->user_progress >= 30 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $slot->user_progress }}%"></div>
                                </div>
                                <span class="text-xs text-gray-400">{{ number_format($slot->user_progress, 0) }}%</span>
                            </div>
                            @else
                            <span class="text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm">
                            @if($slot->checkpoint_pass_rate)
                            <span class="{{ $slot->checkpoint_pass_rate >= 70 ? 'text-green-400' : ($slot->checkpoint_pass_rate >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                                {{ number_format($slot->checkpoint_pass_rate, 1) }}%
                            </span>
                            @else
                            <span class="text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm">
                            @if($slot->item_required_id)
                            <span class="px-2 py-0.5 rounded text-xs {{ $slot->item_available ? 'bg-green-900/50 text-green-400' : 'bg-red-900/50 text-red-400' }}">
                                {{ $slot->item_name ?? 'Item #' . $slot->item_required_id }}
                                {{ $slot->item_available ? '✓' : '✗' }}
                            </span>
                            @else
                            <span class="text-gray-500">None</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">No slots</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-gray-800 rounded-lg p-8 text-center text-gray-500">
        No organized crimes found.
    </div>
    @endforelse
</div>
@endsection
