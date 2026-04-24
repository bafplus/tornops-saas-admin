<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TornOps - Torn City Faction Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-6xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">
                TornOps
            </h1>
            <p class="text-2xl text-gray-400 mb-8">
                Torn City Faction Member Portal
            </p>
            
            <?php $settings = \App\Models\FactionSettings::first(); ?>
            
            <div class="bg-gray-800 rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-blue-400">System Status</h2>
                <div class="grid grid-cols-2 gap-4 text-left">
                    <div class="bg-gray-700 p-4 rounded">
                        <span class="text-gray-400">Database:</span>
                        <span class="text-green-400 ml-2">Connected</span>
                    </div>
                    <div class="bg-gray-700 p-4 rounded">
                        <span class="text-gray-400">API Key:</span>
                        <span class="<?php echo $settings && $settings->torn_api_key ? 'text-green-400' : 'text-red-400'; ?> ml-2">
                            <?php echo $settings && $settings->torn_api_key ? 'Configured' : 'Not Configured'; ?>
                        </span>
                    </div>
                    <div class="bg-gray-700 p-4 rounded">
                        <span class="text-gray-400">Faction ID:</span>
                        <span class="<?php echo $settings && $settings->faction_id ? 'text-green-400' : 'text-red-400'; ?> ml-2">
                            <?php echo $settings && $settings->faction_id ? $settings->faction_id : 'Not Set'; ?>
                        </span>
                    </div>
                    <div class="bg-gray-700 p-4 rounded">
                        <span class="text-gray-400">Admin Account:</span>
                        <span class="text-green-400 ml-2">Active</span>
                    </div>
                </div>
            </div>
            
            <?php if ($settings && $settings->torn_api_key && $settings->faction_id): ?>
            <div class="bg-gray-800 rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-purple-400">Sync Status</h2>
                <p class="text-gray-400">
                    Data is automatically synchronized.<br>
                    Faction sync: every 5 minutes | War sync: every 10 minutes
                </p>
            </div>
            <?php else: ?>
            <div class="bg-gray-800 rounded-lg p-8">
                <h2 class="text-xl font-semibold mb-4 text-purple-400">Complete Setup</h2>
                <p class="text-gray-400 mb-4">Configure your API keys to enable data sync.</p>
                <a href="/setup" class="inline-block bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg transition">
                    Go to Setup
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
