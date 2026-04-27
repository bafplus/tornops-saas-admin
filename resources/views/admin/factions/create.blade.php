@extends('admin.layout')

@section('title', 'Create Faction')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Create New Faction</h1>
        <a href="/admin/factions" class="text-blue-400 hover:text-blue-300">← Back to Factions</a>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <form id="createForm" class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2" for="slug">Slug (URL part)</label>
                    <input type="text" name="slug" id="slug" required 
                        class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="myfaction" pattern="[a-z0-9-]+" title="lowercase letters, numbers, and dashes only">
                    <p class="text-gray-500 text-sm mt-1">Will be at: https://slug.tornops.net</p>
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
                        placeholder="optional">
                </div>

                <div class="flex items-center gap-4">
                    <input type="checkbox" name="is_trial" id="is_trial" value="1"
                        class="w-4 h-4 bg-gray-700 border-gray-600 rounded">
                    <label for="is_trial" class="text-gray-300">Trial period (free)</label>
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-3 rounded text-white font-semibold">
                    Create Faction
                </button>
            </form>
        </div>

        <div class="bg-gray-900 rounded-lg border border-gray-700 p-4">
            <h2 class="text-lg font-semibold mb-3 text-blue-400">Output</h2>
            <pre id="output" class="text-sm text-green-400 font-mono whitespace-pre-wrap h-[500px] overflow-y-auto">Ready...</pre>
            
            <div id="resultActions" class="hidden mt-4 pt-4 border-t border-gray-700">
                <a id="successLink" href="/admin/factions" 
                    class="inline-block bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white">
                    ← Back to Factions
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('createForm');
const output = document.getElementById('output');
const submitBtn = document.getElementById('submitBtn');
const resultActions = document.getElementById('resultActions');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    const slug = formData.get('slug');
    const name = formData.get('name');
    const tornFactionId = formData.get('torn_faction_id');
    const tornApiKey = formData.get('torn_api_key') || '';
    const isTrial = formData.get('is_trial') ? 1 : 0;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';
    resultActions.classList.add('hidden');
    output.textContent = '';
    
    try {
        const response = await fetch('/admin/factions/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                slug, name, torn_faction_id: tornFactionId, torn_api_key: tornApiKey, is_trial: isTrial
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            output.textContent = data.output || 'Done!';
            resultActions.classList.remove('hidden');
        } else {
            output.textContent = 'Error: ' + (data.error || 'Unknown error');
            output.classList.remove('text-green-400');
            output.classList.add('text-red-400');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Faction';
        }
    } catch (err) {
        output.textContent = 'Error: ' + err.message;
        output.classList.remove('text-green-400');
        output.classList.add('text-red-400');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Faction';
    }
});
</script>
@endsection