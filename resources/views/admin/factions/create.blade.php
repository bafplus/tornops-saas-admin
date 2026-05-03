<!DOCTYPE html>
<html>
<head>
    <title>Create Faction - TornOps Admin</title>
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
            <h2 class="text-2xl font-bold mb-6">Create New Faction</h2>

            <form method="POST" action="/admin/factions">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2">Faction Name</label>
                    <input type="text" name="name" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Slug (URL-friendly)</label>
                    <input type="text" name="slug" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Torn Faction ID</label>
                    <input type="number" name="torn_faction_id" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" name="is_trial" id="is_trial" value="1" class="w-4 h-4">
                    <label for="is_trial" class="text-gray-700">Free / Trial</label>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Payment Status</label>
                    <select name="payment" class="w-full border p-2 rounded">
                        <option value="Due">Due</option>
                        <option value="Paid">Paid</option>
                        <option value="Disabled">Disabled</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Amount (items)</label>
                    <input type="number" name="amount" step="1" value="0" class="w-full border p-2 rounded">
                </div>
                <button type="submit" class="w-full bg-red-500 text-white p-2 rounded">Create Faction</button>
            </form>
        </div>
    </div>
</body>
</html>
