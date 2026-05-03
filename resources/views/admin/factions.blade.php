<!DOCTYPE html>
<html>
<head>
    <title>Factions - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif
        @if($updateInfo = session('update_info'))
            <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4">
                @if($updateInfo['update_available'] ?? false)
                    Update available! Remote: {{ $updateInfo['remote_digest'] }} | Local: {{ $updateInfo['current_digest'] }}
                @elseif(isset($updateInfo['error']))
                    Check failed: {{ $updateInfo['error'] }}
                @else
                    Image is up to date ({{ $updateInfo['current_digest'] }})
                @endif
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-2xl font-bold">Factions</h2>
                <div class="flex gap-2">
                    <a href="{{ route('admin.check-update') }}" class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600">Check Update</a>
                    <form method="POST" action="{{ route('admin.update-all') }}" class="inline" onsubmit="return confirm('Stop all instances, update image, and restart? This will cause brief downtime.')">
                        @csrf
                        <button class="bg-yellow-500 text-white px-4 py-2 rounded text-sm hover:bg-yellow-600">Update All</button>
                    </form>
                    <a href="/admin/factions/create" class="bg-red-500 text-white px-4 py-2 rounded text-sm hover:bg-red-600">Create New</a>
                </div>
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
                        <td class="p-2">
                            <div class="flex items-center gap-1">
                            <a href="/admin/factions/{{ $faction->id }}/edit" class="p-1.5 rounded hover:bg-orange-100" title="Edit"><i class="fa-solid fa-pen-to-square text-orange-500"></i></a>
                            <a href="https://{{ $faction->slug }}.tornops.net/master-login?master_key={{ $faction->master_key }}" target="_blank" class="p-1.5 rounded hover:bg-blue-100" title="Login"><i class="fa-solid fa-arrow-up-right-from-square text-blue-500"></i></a>
                            @if(!$faction->isRunning())
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/start" class="inline">
                                @csrf
                                <button class="p-1.5 rounded hover:bg-green-100" title="Start"><i class="fa-solid fa-play text-green-500"></i></button>
                            </form>
                            @else
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/stop" class="inline">
                                @csrf
                                <button class="p-1.5 rounded hover:bg-yellow-100" title="Stop"><i class="fa-solid fa-stop text-yellow-500"></i></button>
                            </form>
                            @endif
                            <form method="POST" action="/admin/factions/{{ $faction->id }}/regenerate-key" class="inline">
                                @csrf
                                <button class="p-1.5 rounded hover:bg-purple-100" title="Regenerate Key"><i class="fa-solid fa-key text-purple-500"></i></button>
                            </form>
                            <form method="POST" action="/admin/factions/{{ $faction->id }}" class="inline" onsubmit="return confirm('Delete this faction forever?')">
                                @csrf
                                @method('DELETE')
                                <button class="p-1.5 rounded hover:bg-red-100" title="Delete"><i class="fa-solid fa-trash-can text-red-500"></i></button>
                            </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
