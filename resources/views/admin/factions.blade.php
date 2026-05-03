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
                        <td class="p-2 space-x-2">
                            <a href="/admin/factions/{{ $faction->id }}/edit" class="text-orange-500 hover:underline">Edit</a>
                            <a href="https://{{ $faction->slug }}.tornops.net/master-login?master_key={{ $faction->master_key }}" target="_blank" class="text-blue-500 hover:underline">Login</a>
                            @if(!$faction->isRunning())
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/start" class="inline">
                                @csrf
                                <button class="text-green-500">Start</button>
                            </form>
                            @else
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/stop" class="inline">
                                @csrf
                                <button class="text-yellow-500">Stop</button>
                            </form>
                            @endif
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/regenerate-key" class="inline">
                                @csrf
                                <button class="text-purple-500">New Key</button>
                            </form>
                            <form method="POST" action="/admin/factions/{{ $faction->id }}" class="inline" onsubmit="return confirm('Delete?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-500">Delete</button>
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
