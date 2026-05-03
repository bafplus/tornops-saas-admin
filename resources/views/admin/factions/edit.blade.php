<!DOCTYPE html>
<html>
<head>
    <title>Edit Faction - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto">
            <a href="/admin/factions" class="text-xl font-bold">TornOps Admin</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <div class="bg-white rounded-lg shadow p-6 max-w-lg">
            <h2 class="text-2xl font-bold mb-6">Edit Faction: {{ $faction->name }}</h2>

            <form method="POST" action="/admin/factions/{{ $faction->id }}">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block mb-2">Faction Name</label>
                    <input type="text" name="name" value="{{ $faction->name }}" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Slug (URL-friendly)</label>
                    <input type="text" name="slug" value="{{ $faction->slug }}" class="w-full border p-2 rounded" required>
                    @error('slug')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Torn Faction ID</label>
                    <input type="number" name="torn_faction_id" value="{{ $faction->torn_faction_id }}" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" name="is_trial" id="is_trial" value="1" class="w-4 h-4" {{ $faction->is_trial ? 'checked' : '' }}>
                    <label for="is_trial" class="text-gray-700">Free / Trial</label>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Payment Status</label>
                    <select name="payment" class="w-full border p-2 rounded">
                        <option value="Due" {{ $faction->payment == 'Due' ? 'selected' : '' }}>Due</option>
                        <option value="Paid" {{ $faction->payment == 'Paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Disabled" {{ $faction->payment == 'Disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Amount (items)</label>
                    <input type="number" name="amount" step="1" value="{{ $faction->amount ?? 0 }}" class="w-full border p-2 rounded">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-red-500 text-white p-2 rounded hover:bg-red-600">Save Changes</button>
                    <a href="/admin/factions" class="flex-1 bg-gray-300 text-gray-700 p-2 rounded text-center hover:bg-gray-400">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
