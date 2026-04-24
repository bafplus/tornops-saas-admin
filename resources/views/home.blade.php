<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TornOps SaaS - Faction Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-blue-400 mb-4">TornOps SaaS</h1>
            <p class="text-xl text-gray-400">Professional faction management for Torn.com</p>
        </div>
        
        <div class="max-w-md mx-auto bg-gray-800 rounded-lg p-8 border border-gray-700">
            <h2 class="text-2xl font-semibold mb-6">Order Your Faction Tool</h2>
            
            @if(session('success'))
                <div class="bg-green-900/50 border border-green-700 text-green-200 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif
            
            <form action="{{ route('order') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-gray-400 mb-2">Faction Name</label>
                    <input type="text" name="faction_name" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Short URL Slug</label>
                    <input type="text" name="slug" required placeholder="e.g. TRR"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Torn Faction ID</label>
                    <input type="number" name="torn_faction_id" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Email</label>
                    <input type="email" name="email" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-3 rounded font-semibold">
                    Submit Order
                </button>
            </form>
        </div>
        
        <div class="text-center mt-12 text-gray-500">
            <p>Already have an account? <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300">Login</a></p>
        </div>
    </div>
</body>
</html>