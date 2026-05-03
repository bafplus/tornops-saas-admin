<!DOCTYPE html>
<html>
<head>
    <title>Factions - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between">
            <h1 class="text-xl font-bold">TornOps Admin</h1>
            <form method="POST" action="/admin/logout">
                @csrf
                <button class="text-red-500">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between mb-4">
                <h2 class="text-2xl font-bold">Factions</h2>
                <a href="/admin/factions/create" class="bg-red-500 text-white px-4 py-2 rounded">Create New</a>
            </div>

            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-2">Name</th>
                        <th class="text-left p-2">Slug</th>
                        <th class="text-left p-2">Faction ID</th>
                        <th class="text-left p-2">Trial</th>
                        <th class="text-left p-2">Status</th>
                        <th class="text-left p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($factions as $faction)
                    <tr class="border-b">
                        <td class="p-2">{{ $faction->name }}</td>
                        <td class="p-2">{{ $faction->slug }}</td>
                        <td class="p-2">{{ $faction->torn_faction_id }}</td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded {{ $faction->is_trial ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $faction->is_trial ? 'Free' : '-' }}
                            </span>
                        </td>
                        <td class="p-2">
                            <span class="px-2 py-1 rounded {{ $faction->isRunning() ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $faction->isRunning() ? 'Running' : 'Stopped' }}
                            </span>
                        </td>
                        <td class="p-2 space-x-1 whitespace-nowrap">
                            <a href="/admin/factions/{{ $faction->id }}/edit" class="inline-block p-1 rounded hover:bg-orange-100" title="Edit">
                                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="https://{{ $faction->slug }}.tornops.net/master-login?master_key={{ $faction->master_key }}" target="_blank" class="inline-block p-1 rounded hover:bg-blue-100" title="Login">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            @if(!$faction->isRunning())
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/start" class="inline">
                                @csrf
                                <button class="inline-block p-1 rounded hover:bg-green-100" title="Start">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                            </form>
                            @else
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/stop" class="inline">
                                @csrf
                                <button class="inline-block p-1 rounded hover:bg-yellow-100" title="Stop">
                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/regenerate-key" class="inline">
                                @csrf
                                <button class="inline-block p-1 rounded hover:bg-purple-100" title="Regenerate Key">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                </button>
                            </form>
                            <form method="POST" action="/admin/factions/{{ $faction->id }}" class="inline" onsubmit="return confirm('Delete this faction forever?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-block p-1 rounded hover:bg-red-100" title="Delete">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
