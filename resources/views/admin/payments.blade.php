<!DOCTYPE html>
<html>
<head>
    <title>Payments - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between">
            <div class="flex items-center gap-4">
                <a href="/admin/factions" class="text-xl font-bold text-gray-800"><i class="fa-solid fa-building"></i> TornOps Admin</a>
                <a href="/admin/factions" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-list"></i> Factions</a>
                <a href="/admin/settings" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-cog"></i> Settings</a>
                <a href="/admin/payments" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-credit-card"></i> Payments</a>
            </div>
            <form method="POST" action="/admin/logout">
                @csrf
                <button class="text-red-500"><i class="fa-solid fa-sign-out-alt"></i> Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h3 class="font-semibold"><i class="fa-solid fa-clock"></i> Payment Sync</h3>
                    <span class="text-sm text-gray-500">
                        Last run:
                        @if($lastEventRun = \App\Models\AdminSetting::get('last_event_run'))
                            {{ \Carbon\Carbon::createFromTimestamp((int)$lastEventRun)->format('d M Y H:i:s') }}
                        @else
                            <span class="text-yellow-600">Never</span>
                        @endif
                    </span>
                    <span class="text-sm text-gray-400">| Next: every minute</span>
                </div>
                <form method="POST" action="{{ route('admin.payments.run') }}" class="inline">
                    @csrf
                    <button class="bg-blue-500 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-600">
                        <i class="fa-solid fa-play"></i> Run Now
                    </button>
                </form>
            </div>
        </div>

        <div class="flex justify-between mb-4">
            <h2 class="text-2xl font-bold">Payments</h2>
            <button onclick="toggleManualForm()" class="bg-red-500 text-white px-4 py-2 rounded text-sm hover:bg-red-600">
                <i class="fa-solid fa-plus"></i> Add Manual Payment
            </button>
        </div>

        <div id="manual-form" class="bg-white rounded-lg shadow p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold mb-4">Record Manual Payment</h3>
            <form method="POST" action="/admin/payments" class="max-w-lg">
                @csrf
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Faction</label>
                    <select name="faction_id" class="w-full border p-2 rounded" required>
                        <option value="">Select faction...</option>
                        @foreach(\App\Models\Faction::orderBy('name')->get() as $f)
                            <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Item</label>
                    <input type="text" name="item_name" value="{{ $settings['default_payment_item'] }}" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Quantity</label>
                    <input type="number" name="quantity" value="{{ $settings['default_payment_amount'] }}" min="1" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Description (optional)</label>
                    <input type="text" name="description" class="w-full border p-2 rounded" placeholder="e.g. Payment for faction 55742">
                </div>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Record Payment</button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-2">Date</th>
                        <th class="text-left p-2">Faction</th>
                        <th class="text-left p-2">Payer</th>
                        <th class="text-left p-2">Item</th>
                        <th class="text-left p-2">Qty</th>
                        <th class="text-left p-2">Expires</th>
                        <th class="text-left p-2">Source</th>
                        <th class="text-left p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr class="border-b">
                        <td class="p-2 text-sm">{{ $payment->created_at->format('d M H:i') }}</td>
                        <td class="p-2">{{ $payment->faction ? $payment->faction->name : ($payment->faction_id ? 'ID:'.$payment->faction_id : '-') }}</td>
                        <td class="p-2 text-sm">{{ $payment->payer_name ?: ($payment->manual ? 'Manual' : '-') }}</td>
                        <td class="p-2">{{ $payment->item_name }}</td>
                        <td class="p-2">{{ $payment->quantity }}</td>
                        <td class="p-2 text-sm">{{ $payment->expires_at ? $payment->expires_at->format('d M Y') : '-' }}</td>
                        <td class="p-2">
                            @if($payment->matched_instance)
                                <span class="text-green-600 text-sm"><i class="fa-solid fa-check"></i> Matched</span>
                            @elseif($payment->manual)
                                <span class="text-blue-600 text-sm"><i class="fa-solid fa-hand"></i> Manual</span>
                            @else
                                <span class="text-yellow-600 text-sm"><i class="fa-solid fa-question"></i> Unmatched</span>
                            @endif
                            @if($payment->raw_event)
                            <div class="relative inline-block group">
                                <i class="fa-solid fa-scroll text-xs text-gray-400 hover:text-gray-600 cursor-help" title="Click to view raw event"></i>
                                <div class="absolute bottom-full right-0 mb-2 w-96 p-3 bg-gray-800 text-gray-200 text-xs rounded-lg shadow-xl hidden group-hover:block z-50 break-all">
                                    <div class="font-semibold mb-1 text-gray-400">Raw Event:</div>
                                    {{ strip_tags($payment->raw_event) }}
                                </div>
                            </div>
                            @endif
                        </td>
                        <td class="p-2">
                            @if(!$payment->matched_instance && !$payment->manual)
                            <button onclick="showMatchForm({{ $payment->id }})" class="text-blue-500 text-sm hover:underline">Match</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="p-4 text-center text-gray-500">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $payments->links() }}</div>
        </div>

        @foreach($payments as $payment)
        @if(!$payment->matched_instance && !$payment->manual)
        <div id="match-form-{{ $payment->id }}" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full">
                <h3 class="text-lg font-semibold mb-4">Match Payment</h3>
                <form method="POST" action="/admin/payments/{{ $payment->id }}/match">
                    @csrf
                    <div class="mb-3">
                        <label class="block mb-1 text-sm">Faction</label>
                        <select name="faction_id" class="w-full border p-2 rounded" required>
                            <option value="">Select faction...</option>
                            @foreach(\App\Models\Faction::orderBy('name')->get() as $f)
                                <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Match</button>
                        <button type="button" onclick="hideMatchForm({{ $payment->id }})" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endforeach
    </div>

    <script>
    function toggleManualForm() {
        document.getElementById('manual-form').classList.toggle('hidden');
    }
    function showMatchForm(id) {
        document.getElementById('match-form-' + id).classList.remove('hidden');
    }
    function hideMatchForm(id) {
        document.getElementById('match-form-' + id).classList.add('hidden');
    }
    </script>
</body>
</html>
