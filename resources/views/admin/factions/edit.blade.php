<!DOCTYPE html>
<html>
<head>
    <title>Edit Faction - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

                <hr class="my-4">

                <h3 class="text-lg font-semibold mb-3">Subscription</h3>
                <div class="mb-4">
                    <label class="block mb-2">Type</label>
                    <select name="subscription_type" class="w-full border p-2 rounded">
                        <option value="free" {{ $faction->subscription_type == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="trial" {{ $faction->subscription_type == 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="paid" {{ $faction->subscription_type == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Payment Item</label>
                    <input type="text" name="payment_item" value="{{ $faction->payment_item ?? 'xanax' }}" class="w-full border p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Payment Amount (per week)</label>
                    <input type="number" name="payment_amount" value="{{ $faction->payment_amount ?? 1 }}" min="1" class="w-full border p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ $faction->expires_at ? $faction->expires_at->format('Y-m-d\TH:i') : '' }}" class="w-full border p-2 rounded">
                    <p class="text-xs text-gray-400 mt-1">Leave empty for no expiration (free accounts).</p>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" name="is_trial" id="is_trial" value="1" class="w-4 h-4" {{ $faction->is_trial ? 'checked' : '' }}>
                    <label for="is_trial" class="text-gray-700">Free / Trial (old field)</label>
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
