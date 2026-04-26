@extends('admin.layout')

@section('title', 'Create Faction')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Create New Faction</h1>
        <a href="/admin/factions" class="text-blue-400 hover:text-blue-300">← Back to Factions</a>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
        <form action="/admin/factions/create" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-gray-300 mb-2" for="slug">Slug ( URL part )</label>
                <input type="text" name="slug" id="slug" required 
                    class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                    placeholder="myfaction" pattern="[a-z0-9-]+" title="lowercase letters, numbers, and dashes only">
                <p class="text-gray-500 text-sm mt-1">Will be available at: https://slug.tornops.net</p>
            </div>

            <div>
                <label class="block text-gray-300 mb-2" for="name">Faction Name</label>
                <input type="text" name="name" id="name" required 
                    class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                    placeholder="My Faction">
            </div>

            <div>
                <label class="block text-gray-300 mb-2" for="torn_faction_id">Torn Faction ID</label>
                <input type="number" name="torn_faction_id" id="torn_faction_id" required 
                    class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                    placeholder="12345">
            </div>

            <div>
                <label class="block text-gray-300 mb-2" for="torn_api_key">Torn API Key</label>
                <input type="text" name="torn_api_key" id="torn_api_key" 
                    class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                    placeholder="Your Torn API key (optional - can be set later)">
            </div>

            <div class="flex items-center gap-4">
                <input type="checkbox" name="is_trial" id="is_trial" value="1"
                    class="w-4 h-4 bg-gray-700 border-gray-600 rounded">
                <label for="is_trial" class="text-gray-300">Trial period (free)</label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-3 rounded text-white font-semibold">
                Create Faction
            </button>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-xl font-semibold mb-4 text-blue-400">Creation Steps</h2>
        
        <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">1</span>
                <div>
                    <p class="font-semibold">Creating Database Record</p>
                    <p class="text-gray-400">Saves faction details to database</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">2</span>
                <div>
                    <p class="font-semibold">Creating Directories</p>
                    <p class="text-gray-400">Sets up storage, data, and cache directories</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">3</span>
                <div>
                    <p class="font-semibold">Cloning TornOps</p>
                    <p class="text-gray-400">Downloads latest TornOps code from GitHub</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">4</span>
                <div>
                    <p class="font-semibold">Installing Dependencies</p>
                    <p class="text-gray-400">Runs composer install</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">5</span>
                <div>
                    <p class="font-semibold">Setting Up Database</p>
                    <p class="text-gray-400">Creates SQLite database and runs migrations</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">6</span>
                <div>
                    <p class="font-semibold">Starting Server</p>
                    <p class="text-gray-400">Starts PHP server on available port</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-green-600 flex items-center justify-center text-xs">7</span>
                <div>
                    <p class="font-semibold">Configuring DNS</p>
                    <p class="text-gray-400">Creates Cloudflare DNS record and tunnel route</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection