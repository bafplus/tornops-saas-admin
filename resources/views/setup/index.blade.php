<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TornOps Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="max-w-xl w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-400 mb-2">TornOps</h1>
            <p class="text-gray-400">Setup Wizard</p>
        </div>

        @php
        $warnings = [];
        $dataDir = '/data';
        $dbFile = '/data/database.sqlite';
        
        // Check if data directory exists, if not it's fine - will be created
        if (!is_dir($dataDir)) {
            $warnings[] = 'Data directory (/data) not found - will be created automatically';
        } else if (!is_writable($dataDir)) {
            $warnings[] = 'Data directory is not writable';
        }
        
        // Check database - if not found it's fine, will be created during setup
        if (!file_exists($dbFile)) {
            $warnings[] = 'Database not found - will be created during setup';
        } else if (!is_writable($dbFile)) {
            $warnings[] = 'Database is not writable';
        }
        @endphp

        @if(!empty($warnings))
        <div class="bg-yellow-900/50 border border-yellow-500 text-yellow-400 px-4 py-3 rounded mb-6">
            <p class="font-semibold mb-2">System Check:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($warnings as $w)
                <li>{{ $w }}</li>
                @endforeach
            </ul>
        </div>
        @else
        <div class="bg-blue-900/50 border border-blue-500 text-blue-400 px-4 py-3 rounded mb-6">
            System ready - database will be created during setup
        </div>
        @endif

        @if(session('success'))
        <div class="bg-green-900/50 border border-green-500 text-green-400 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-900/50 border border-red-500 text-red-400 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-gray-800 rounded-lg shadow-xl p-8">
            <h2 class="text-2xl font-semibold mb-6 text-blue-400">Setup</h2>
            <form action="/setup" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="faction_id">Faction ID</label>
                    <input type="number" name="faction_id" id="faction_id" value="{{ old('faction_id') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="55742" required>
                    <p class="text-gray-500 text-sm mt-1">Your Torn faction ID</p>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="torn_api_key">Torn API Key</label>
                    <input type="text" name="torn_api_key" id="torn_api_key" value="{{ old('torn_api_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Your Torn API key" required minlength="16">
                    <p class="text-gray-500 text-sm mt-1">
                        Get your key at <a href="https://www.torn.com/preferences.php#tab=api" target="_blank" class="text-blue-400 hover:underline">Torn Preferences</a>
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="ffscouter_api_key">FFScouter API Key <span class="text-gray-500">(optional)</span></label>
                    <input type="text" name="ffscouter_api_key" id="ffscouter_api_key" value="{{ old('ffscouter_api_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="FFScouter API key">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="base_domain">Base Domain <span class="text-gray-500">(optional)</span></label>
                    <input type="url" name="base_domain" id="base_domain" value="{{ old('base_domain') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="https://tornops.example.com">
                    <p class="text-gray-500 text-sm mt-1">Used for generating invitation links</p>
                </div>

                <hr class="border-gray-700 my-6">

                <h3 class="text-xl font-semibold mb-4 text-green-400">Admin Account</h3>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="admin_name">Admin Name</label>
                    <input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Your name" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="torn_player_id">Your Torn Player ID</label>
                    <input type="number" name="torn_player_id" id="torn_player_id" value="{{ old('torn_player_id') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Your Torn player ID" required>
                    <p class="text-gray-500 text-sm mt-1">Must be a member of the faction</p>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="admin_password">Password</label>
                    <input type="password" name="admin_password" id="admin_password"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Min 8 characters" required minlength="8">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-300 mb-2" for="admin_password_confirmation">Confirm Password</label>
                    <input type="password" name="admin_password_confirmation" id="admin_password_confirmation"
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Repeat password" required>
                </div>

                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded transition">
                    Complete Setup
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            TornOps - Torn City Faction Portal
        </p>
    </div>
</body>
</html>