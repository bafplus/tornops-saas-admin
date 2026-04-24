@extends('layouts.app')

@section('title', 'Admin - TornOps')

@section('content')
<div class="space-y-6">
    <h1 class="text-3xl font-bold">Admin Panel</h1>

    @if($warActive ?? false)
    <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 text-red-400">
        <div class="flex items-center gap-2 font-semibold text-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Active War in Progress
        </div>
        <p class="text-sm mt-1">War-essential syncs (active wars, attacks) run every minute. Non-essential syncs are disabled to conserve API usage.</p>
    </div>
    @endif

    @if(session('status'))
        <div class="bg-green-900/50 border border-green-700 text-green-400 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if(session('invite_url'))
        <div class="bg-blue-900/50 border border-blue-700 text-blue-400 px-4 py-3 rounded">
            <p class="font-semibold mb-2">Invitation created for {{ session('invited_user_name') }}</p>
            <p class="text-sm mb-2">Send this link to the user:</p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ session('invite_url') }}" 
                       class="flex-1 bg-gray-900 border border-blue-600 rounded px-3 py-2 text-white text-sm font-mono">
                <button onclick="navigator.clipboard.writeText('{{ session('invite_url') }}')" 
                        class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-white text-sm">
                    Copy
                </button>
            </div>
        </div>
    @elseif(session('invite_token'))
        <div class="bg-yellow-900/50 border border-yellow-700 text-yellow-400 px-4 py-3 rounded">
            <p class="font-semibold mb-2">Invitation created for {{ session('invited_user_name') }}</p>
            <p class="text-sm mb-2 text-yellow-300">Base Domain not configured. Use this token to construct the invite URL:</p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ session('invite_token') }}" 
                       class="flex-1 bg-gray-900 border border-yellow-600 rounded px-3 py-2 text-white text-sm font-mono">
                <button onclick="navigator.clipboard.writeText('{{ session('invite_token') }}')" 
                        class="bg-yellow-600 hover:bg-yellow-700 px-3 py-2 rounded text-white text-sm">
                    Copy Token
                </button>
            </div>
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-blue-400">API Settings</h2>
        <form action="/admin/settings" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-400 mb-2">Faction ID</label>
                    <input type="number" name="faction_id" value="{{ $settings->faction_id ?? '' }}" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Torn API Key</label>
                    <input type="text" name="torn_api_key" value="{{ $settings->torn_api_key ?? '' }}" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">FFScouter API Key (Optional)</label>
                    <input type="text" name="ffscouter_api_key" value="{{ $settings->ffscouter_api_key ?? '' }}"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Base Domain (for invite links)</label>
                    <input type="url" name="base_domain" value="{{ $settings->base_domain ?? '' }}" placeholder="https://tornops.example.com"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center text-gray-400 cursor-pointer">
                        <input type="checkbox" name="auto_sync_enabled" value="1" {{ ($settings->auto_sync_enabled ?? false) ? 'checked' : '' }}
                               class="mr-2 w-5 h-5 rounded bg-gray-700 border-gray-600">
                        Enable Auto Sync
                    </label>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded text-white">
                Save Settings
            </button>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-indigo-400">Discord Bot</h2>
        <form action="/admin/settings" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center">
                    <label class="flex items-center text-gray-400 cursor-pointer">
                        <input type="checkbox" name="discord_enabled" value="1" {{ ($settings->discord_enabled ?? false) ? 'checked' : '' }}
                               class="mr-2 w-5 h-5 rounded bg-gray-700 border-gray-600">
                        Enable Discord Bot
                    </label>
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Bot Token</label>
                    <input type="password" name="discord_bot_token" value="{{ $settings->discord_bot_token ?? '' }}" placeholder="MTEwND..."
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Server ID (Discord Server)</label>
                    <input type="number" name="discord_server_id" value="{{ $settings->discord_server_id ?? '' }}" placeholder="123456789012345678"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Default Channel ID (Alerts)</label>
                    <input type="number" name="discord_channel_id" value="{{ $settings->discord_channel_id ?? '' }}" placeholder="123456789012345678"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 px-6 py-2 rounded text-white">
                    Save Settings
                </button>
                <form action="/admin/discord/restart" method="POST">
                    @csrf
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 px-6 py-2 rounded text-white">
                        Restart Bot
                    </button>
                </form>
            </div>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-purple-400">Users</h2>
        
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-300 mb-3">Invite New User</h3>
            <p class="text-gray-500 text-sm mb-3">Player ID will be validated against faction membership before invitation is sent.</p>
            <form action="/admin/users" method="POST" class="flex flex-wrap gap-4 items-end">
                @csrf
                <div>
                    <input type="text" name="name" placeholder="Username" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="number" name="torn_player_id" placeholder="Player ID" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="flex items-center text-gray-400 cursor-pointer mr-4">
                        <input type="checkbox" name="is_admin" value="1" class="mr-2 w-4 h-4 rounded bg-gray-700 border-gray-600">
                        Admin
                    </label>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white">
                    Send Invitation
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-3">Name</th>
                        <th class="p-3">Player ID</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">API Key</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-3">{{ $user->name }}</td>
                        <td class="p-3 font-mono text-blue-400">{{ $user->torn_player_id }}</td>
                        <td class="p-3">
                            @if($user->status === 'active')
                                <span class="px-2 py-1 rounded text-xs bg-green-900 text-green-400">Active</span>
                            @elseif($user->status === 'invited')
                                <span class="px-2 py-1 rounded text-xs bg-yellow-900 text-yellow-400">Invited</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-red-900 text-red-400">Disabled</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if($user->is_admin)
                                <span class="px-2 py-1 rounded text-xs bg-purple-900 text-purple-400">Admin</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">User</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-sm text-gray-400">
                            {{ $user->torn_api_key ? substr($user->torn_api_key, 0, 8).'...' : '-' }}
                        </td>
                        <td class="p-3 text-right">
                            @if($user->status === 'invited')
                                <form action="/admin/users/{{ $user->id }}/regenerate" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-400 hover:text-yellow-300 text-sm">
                                        Resend Invite
                                    </button>
                                </form>
                            @elseif(in_array($user->status, ['active', 'disabled']))
                                <form action="/admin/users/{{ $user->id }}/regenerate" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-orange-400 hover:text-orange-300 text-sm">
                                        Reset Password
                                    </button>
                                </form>
                            @endif
                            <form action="/admin/users/{{ $user->id }}/toggle" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-400 hover:text-blue-300 text-sm">
                                    {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                                <form action="/admin/users/{{ $user->id }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm ml-2"
                                            onclick="return confirm('Delete this user?')">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-gray-300">Sync Commands</h2>
        <div class="flex flex-wrap gap-4">
            <form action="/admin/sync/factions" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">
                    Sync Faction Data
                </button>
            </form>
            <form action="/admin/sync/wars" method="POST">
                @csrf
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-white">
                    Sync War Data
                </button>
            </form>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        @php
            $settings = \App\Models\FactionSettings::first();
            $warModeEnabled = $settings?->war_mode_enabled ?? false;
            $cronOptions = [
                '' => 'Disable',
                '*/1 * * * *' => 'Every 1 min',
                '*/5 * * * *' => 'Every 5 min',
                '*/10 * * * *' => 'Every 10 min',
                '*/30 * * * *' => 'Every 30 min',
                '0 * * * *' => 'Hourly',
                '0 0 * * *' => 'Daily',
            ];
        @endphp
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-yellow-400">API Schedule</h2>
            <form method="POST" action="/admin/war-mode" class="flex items-center gap-2">
                @csrf
                <span class="text-gray-400 text-sm">War Mode:</span>
                <button type="submit" name="enabled" value="{{ $warModeEnabled ? '0' : '1' }}" 
                    class="px-3 py-1 rounded text-sm font-semibold {{ $warModeEnabled ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                    {{ $warModeEnabled ? 'ON' : 'OFF' }}
                </button>
            </form>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-3">Command</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Normal</th>
                        <th class="p-3">War</th>
                        <th class="p-3">Last Run</th>
                        <th class="p-3">Calls</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($apiSchedule as $key => $item)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-3 font-mono text-blue-400">{{ $item['name'] }}</td>
                        <td class="p-3 text-gray-300 text-xs">{{ $item['description'] ?? '' }}</td>
                        <td class="p-3">
                            <form method="POST" action="/admin/job/{{ str_replace('torn:', '', $item['name']) }}" class="flex gap-1">
                                @csrf
                                <select name="schedule" onchange="this.form.submit()" class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-xs">
                                    @foreach($cronOptions as $val => $label)
                                    <option value="{{ $val }}" {{ $item['schedule_raw'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="p-3">
                            @if($warModeEnabled)
                            <form method="POST" action="/admin/job/{{ str_replace('torn:', '', $item['name']) }}" class="flex gap-1">
                                @csrf
                                <select name="war_schedule" onchange="this.form.submit()" class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-xs">
                                    @foreach($cronOptions as $val => $label)
                                    <option value="{{ $val }}" {{ $item['war_schedule_raw'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @else
                            <span class="text-gray-500 text-xs">—</span>
                            @endif
                        </td>
                        <td class="p-3 text-gray-300">{{ $item['last_run'] }}</td>
                        <td class="p-3 text-gray-400">{{ $item['api_calls'] }}</td>
                        <td class="p-3">
                            @php
                            $routeMap = [
                                'faction_sync' => 'factions',
                                'ff_stats' => 'factions',
                                'ranked_wars' => 'wars',
                                'active_wars' => 'active',
                                'war_attacks' => 'attacks',
                                'war_chains' => 'chains',
                                'ocs' => 'ocs',
                                'stocks' => 'stocks',
                                'items' => 'items',
                                'player_profiles' => 'profiles',
                            ];
                            $route = $routeMap[$key] ?? 'wars';
                            @endphp
                            <form action="/admin/sync/{{ $route }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white text-xs">
                                    Run Now
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-gray-900/50 rounded border border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-gray-400 text-sm">
                    <span class="text-yellow-400 font-semibold">Note:</span> 
                    Torn API limits to 100 requests per minute. This system uses caching and smart reuse.
                    @if($warModeEnabled)
                        <br><span class="text-green-400">War Mode is ON - jobs with war schedules run during active war.</span>
                    @else
                        <br>War Mode is OFF - all jobs run their normal schedules.
                    @endif
                </p>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <span class="text-gray-400 text-sm">Actual:</span>
                        <span class="text-2xl font-bold {{ $apiCallsLastMinute > 80 ? 'text-red-400' : ($apiCallsLastMinute > 50 ? 'text-yellow-400' : 'text-green-400') }}">{{ $apiCallsLastMinute }}</span>
                        <span class="text-gray-500 text-sm">/min</span>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-400 text-sm">Expected:</span>
                        <span class="text-2xl font-bold {{ $expectedCallsPerMinute > 80 ? 'text-red-400' : ($expectedCallsPerMinute > 50 ? 'text-yellow-400' : 'text-green-400') }}">{{ $expectedCallsPerMinute }}</span>
                        <span class="text-gray-500 text-sm">/min</span>
                    </div>
                    <form method="POST" action="{{ route('admin.reset-api-calls') }}">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-white border border-gray-600 px-2 py-1 rounded">Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-red-400">Log Viewer</h2>
        <p class="text-gray-400 text-sm mb-4">View recent errors and log entries for debugging.</p>
        <a href="/admin/logs" class="inline-block bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white">
            View Logs
        </a>
    </div>
</div>
@endsection