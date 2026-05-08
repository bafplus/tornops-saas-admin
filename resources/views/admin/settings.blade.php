<!DOCTYPE html>
<html>
<head>
    <title>Settings - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between">
            <div class="flex items-center gap-4">
                <a href="/admin/factions" class="text-xl font-bold">TornOps Admin</a>
                <a href="/admin/settings" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-cog"></i> Settings</a>
                <a href="/admin/payments" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-credit-card"></i> Payments</a>
            </div>
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

        <div class="bg-white rounded-lg shadow p-6 max-w-lg">
            <h2 class="text-2xl font-bold mb-6">Admin Settings</h2>

            <form method="POST" action="/admin/settings">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block mb-2">Torn API Key <span class="text-xs text-gray-400">(for payment sync)</span></label>
                    <input type="text" name="torn_api_key" value="{{ $settings['torn_api_key'] }}" class="w-full border p-2 rounded font-mono" placeholder="Enter your Torn API key">
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Default Payment Item</label>
                    <input type="text" name="default_payment_item" value="{{ $settings['default_payment_item'] }}" class="w-full border p-2 rounded">
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Default Payment Amount (per week)</label>
                    <input type="number" name="default_payment_amount" value="{{ $settings['default_payment_amount'] }}" min="1" class="w-full border p-2 rounded">
                </div>
                <button type="submit" class="w-full bg-red-500 text-white p-2 rounded hover:bg-red-600">Save Settings</button>
            </form>
        </div>
    </div>
</body>
</html>
