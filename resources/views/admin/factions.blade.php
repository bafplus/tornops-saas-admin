@extends('admin.layout')

@section('title', 'Factions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Factions</h1>
    <form action="/admin/factions" method="POST" class="flex space-x-2">
        @csrf
        <input type="text" name="slug" placeholder="slug" required class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
        <input type="text" name="name" placeholder="Faction name" required class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
        <input type="number" name="torn_faction_id" placeholder="Torn ID" required class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">Add Faction</button>
    </form>
</div>

<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">Slug</th>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Torn ID</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Trial</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            @forelse($factions ?? [] as $faction)
            <tr>
                <td class="px-4 py-3 font-mono text-blue-400">
                    {{ $faction->slug }}
                    @if($faction->port)
                    <span class="text-xs text-gray-500 block">:{{ $faction->port }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">{{ $faction->name }}</td>
                <td class="px-4 py-3 text-gray-400">#{{ $faction->torn_faction_id }}</td>
                <td class="px-4 py-3">
                    @php $statusClass = match($faction->status) {
                        'active' => 'bg-green-900 text-green-200',
                        'creating' => 'bg-blue-900 text-blue-200',
                        'error' => 'bg-red-900 text-red-200',
                        default => 'bg-yellow-900 text-yellow-200'
                    }; @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $statusClass }}">
                        {{ $faction->status }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    {{ $faction->is_trial ? 'Yes' : '-' }}
                </td>
                <td class="px-4 py-3 space-x-2">
                    @if($faction->status === 'active')
                    <a href="https://{{ $faction->slug }}.tornops.net" target="_blank" class="text-green-400 hover:text-green-300">Visit</a>
                    @endif
                    <a href="/admin/factions/{{ $faction->slug }}/login?master_key={{ $faction->master_key }}" class="text-blue-400 hover:text-blue-300">Login</a>
                    <a href="/admin/factions/{{ $faction->slug }}/regenerate-key" class="text-yellow-400 hover:text-yellow-300">New Key</a>
                </td>
            </tr>
            @if($faction->log)
            <tr>
                <td colspan="6" class="px-4 py-2 bg-gray-900">
                    <pre class="text-xs text-gray-500 font-mono whitespace-pre-wrap">{{ $faction->log }}</pre>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No factions yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection