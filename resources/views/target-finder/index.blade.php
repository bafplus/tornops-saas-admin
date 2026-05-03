@extends('layouts.app')

@section('title', 'Target Finder - TornOps')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">FFScout Target Finder</h1>
        <p class="text-gray-400">Find attack targets based on FFscore and level criteria</p>
    </div>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
        {{ session('error') }}
    </div>
    @endif

    @if(!Auth::user()->torn_api_key)
    <div class="mb-4 p-4 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-400">
        No API key found. Please add your Torn API key in <a href="/settings" class="underline">Settings</a> to use Target Finder.
    </div>
    @else
    <div id="api-status-banner" class="mb-4 p-4 rounded-lg border hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span id="status-icon" class="text-2xl"></span>
                <span id="status-text" class="font-medium"></span>
            </div>
            <button id="btn-register" onclick="showRegisterModal()" class="hidden px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm">
                Register Key
            </button>
        </div>
    </div>
    @endif

    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-200">Find Targets</h3>
                @if($userFfScore || $userEstStats)
                <div class="flex items-center gap-4 mt-1 text-sm">
                    <span class="text-gray-400">Your FF: <span class="text-green-400 font-medium">{{ $userFfScore ? number_format($userFfScore, 2) : '--' }}</span></span>
                    <span class="text-gray-400">Est: <span class="text-blue-400 font-medium">{{ $userEstStats ?? '--' }}</span></span>
                </div>
                @endif
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-400">Targets:</label>
                    <select id="target-limit" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm">
                        <option value="1">1</option>
                        <option value="5">5</option>
                        <option value="10">10</option>
                    </select>
                </div>
                <button onclick="toggleSettings()" id="btn-toggle-settings" class="px-4 py-1.5 bg-gray-600 hover:bg-gray-500 text-white rounded-lg font-medium text-sm flex items-center gap-2">
                    <svg id="settings-chevron" class="w-4 h-4 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    Settings
                </button>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <button onclick="fetchTarget('easy')" id="btn-easy" disabled
                class="flex-1 px-6 py-3 bg-green-700 hover:bg-green-600 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-lg font-medium flex items-center justify-center gap-2">
                <span>⚡</span> Get Easy Targets
            </button>
            <button onclick="fetchTarget('good')" id="btn-good" disabled
                class="flex-1 px-6 py-3 bg-orange-700 hover:bg-orange-600 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-lg font-medium flex items-center justify-center gap-2">
                <span>🔥</span> Get Good Targets
            </button>
        </div>

        <div id="loading-area" class="hidden">
            <div class="flex items-center justify-center gap-3 py-4 text-gray-400">
                <div class="w-6 h-6 border-2 border-gray-600 border-t-gray-400 rounded-full animate-spin"></div>
                <span id="loading-text">Finding targets...</span>
            </div>
        </div>

        <div id="error-area" class="hidden">
            <div class="p-4 bg-red-900/30 rounded-lg border border-red-700 text-red-400" id="error-message"></div>
        </div>

        <div id="result-area" class="hidden">
            <div id="targets-list" class="space-y-3"></div>
        </div>
    </div>

    <div id="settings-panel" class="hidden mb-6">
        <form action="/target-finder/settings" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-green-900/50 flex items-center justify-center text-xl">⚡</div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-200">Easy Targets</h2>
                            <span class="text-sm text-gray-500">Lower FFscore opponents</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Fair Fight Range</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.1" min="1" max="5" name="easy[minFF]" value="{{ $settings['easy']['minFF'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                                <span class="text-gray-500">→</span>
                                <input type="number" step="0.1" min="1" max="5" name="easy[maxFF]" value="{{ $settings['easy']['maxFF'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Level Range</label>
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" max="100" name="easy[minLevel]" value="{{ $settings['easy']['minLevel'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                                <span class="text-gray-500">→</span>
                                <input type="number" min="1" max="100" name="easy[maxLevel]" value="{{ $settings['easy']['maxLevel'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400">Available targets:</span>
                                <span id="easy-count" class="text-gray-500">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-orange-900/50 flex items-center justify-center text-xl">🔥</div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-200">Good Targets</h2>
                            <span class="text-sm text-gray-500">Higher FFscore opponents</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Fair Fight Range</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.1" min="1" max="5" name="good[minFF]" value="{{ $settings['good']['minFF'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                                <span class="text-gray-500">→</span>
                                <input type="number" step="0.1" min="1" max="5" name="good[maxFF]" value="{{ $settings['good']['maxFF'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Level Range</label>
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" max="100" name="good[minLevel]" value="{{ $settings['good']['minLevel'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                                <span class="text-gray-500">→</span>
                                <input type="number" min="1" max="100" name="good[maxLevel]" value="{{ $settings['good']['maxLevel'] }}"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center">
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400">Available targets:</span>
                                <span id="good-count" class="text-gray-500">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-200 mb-4">Filters</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="inactiveOnly" value="1" {{ $settings['inactiveOnly'] ? 'checked' : '' }}
                            class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-600">
                        <div>
                            <div class="text-gray-200">Inactive Only</div>
                            <div class="text-sm text-gray-500">Target players inactive 14+ days</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="factionlessOnly" value="1" {{ $settings['factionlessOnly'] ? 'checked' : '' }}
                            class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-600">
                        <div>
                            <div class="text-gray-200">Factionless Only</div>
                            <div class="text-sm text-gray-500">Target players without a faction</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="verifyStatus" value="1" {{ $settings['verifyStatus'] ? 'checked' : '' }}
                            class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-600">
                        <div>
                            <div class="text-gray-200">Verify Status</div>
                            <div class="text-sm text-gray-500">Check target is Okay (slower)</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<div id="register-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-xl border border-gray-700 w-full max-w-md shadow-2xl">
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-white">Register with FFScout</h2>
            <p class="text-gray-400 text-sm mt-1">Register your API key to use the Target Finder</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4">
                <p class="text-sm text-blue-300">
                    Before registering, you must read and agree to the FFScout Data Policy and Terms.
                </p>
                <a href="https://ffscouter.com" target="_blank" class="inline-flex items-center gap-1 mt-2 text-blue-400 hover:text-blue-300 text-sm font-medium">
                    Read FFScout Data Policy
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="agree-policy" class="mt-1 w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-600">
                <span class="text-sm text-gray-300">I have read and agree to the FFScout Data Policy and Terms</span>
            </label>
            <div id="register-error" class="hidden p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"></div>
        </div>
        <div class="p-6 border-t border-gray-700 flex gap-3">
            <button onclick="closeRegisterModal()" class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg font-medium">
                Cancel
            </button>
            <button onclick="registerKey()" id="btn-submit-register" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                Register API Key
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const hasApiKey = {{ Auth::user()->torn_api_key ? 'true' : 'false' }};
let settingsOpen = false;

document.addEventListener('DOMContentLoaded', function() {
    if (hasApiKey) {
        checkKeyStatus();
    }

    setTimeout(() => {
        document.querySelectorAll('#api-status-banner.bg-green-900\\/30').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.classList.add('hidden'), 500);
        });
        document.querySelectorAll('.bg-green-900\\/50, .bg-red-900\\/50, .bg-yellow-900\\/50').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 4000);
});

function toggleSettings() {
    settingsOpen = !settingsOpen;
    const panel = document.getElementById('settings-panel');
    const chevron = document.getElementById('settings-chevron');
    const btn = document.getElementById('btn-toggle-settings');

    if (settingsOpen) {
        panel.classList.remove('hidden');
        chevron.classList.remove('rotate-0');
        chevron.classList.add('-rotate-90');
        btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.remove('bg-gray-600', 'hover:bg-gray-500');
    } else {
        panel.classList.add('hidden');
        chevron.classList.remove('-rotate-90');
        chevron.classList.add('rotate-0');
        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.add('bg-gray-600', 'hover:bg-gray-500');
    }
}

function checkKeyStatus() {
    fetch('/target-finder/check-key')
        .then(res => res.json())
        .then(data => {
            const banner = document.getElementById('api-status-banner');
            const icon = document.getElementById('status-icon');
            const text = document.getElementById('status-text');
            const btnRegister = document.getElementById('btn-register');

            banner.classList.remove('hidden');

            if (data.success && data.isRegistered) {
                banner.className = 'mb-4 p-4 rounded-lg border bg-green-900/30 border-green-700';
                icon.textContent = '✓';
                text.textContent = 'API key registered at FFScout';
                text.className = 'font-medium text-green-400';
                btnRegister.classList.add('hidden');

                document.getElementById('btn-easy').disabled = false;
                document.getElementById('btn-good').disabled = false;
                refreshCounts();
            } else {
                banner.className = 'mb-4 p-4 rounded-lg border bg-red-900/30 border-red-700';
                icon.textContent = '✕';
                text.textContent = 'API key not registered at FFScout';
                text.className = 'font-medium text-red-400';
                btnRegister.classList.remove('hidden');
            }
        })
        .catch(() => {
            const banner = document.getElementById('api-status-banner');
            banner.classList.remove('hidden');
            banner.className = 'mb-4 p-4 rounded-lg border bg-yellow-900/30 border-yellow-700';
            document.getElementById('status-icon').textContent = '?';
            document.getElementById('status-text').textContent = 'Could not verify API key status';
            document.getElementById('status-text').className = 'font-medium text-yellow-400';
        });
}

function showRegisterModal() {
    document.getElementById('register-modal').classList.remove('hidden');
    document.getElementById('agree-policy').checked = false;
    document.getElementById('register-error').classList.add('hidden');
}

function closeRegisterModal() {
    document.getElementById('register-modal').classList.add('hidden');
}

function registerKey() {
    const agreePolicy = document.getElementById('agree-policy').checked;
    const errorEl = document.getElementById('register-error');
    const submitBtn = document.getElementById('btn-submit-register');

    if (!agreePolicy) {
        errorEl.textContent = 'You must agree to the data policy to register.';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Registering...';

    fetch('/target-finder/register-key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ agree_to_policy: true })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeRegisterModal();
            checkKeyStatus();
        } else {
            errorEl.textContent = data.error || 'Registration failed';
            errorEl.classList.remove('hidden');
        }
    })
    .catch(err => {
        errorEl.textContent = 'Error: ' + err.message;
        errorEl.classList.remove('hidden');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Register API Key';
    });
}

function refreshCounts() {
    fetchTargetCount('easy');
    fetchTargetCount('good');
}

function fetchTargetCount(type) {
    fetch(`/target-finder/count/${type}`)
        .then(res => res.json())
        .then(data => {
            const el = document.getElementById(`${type}-count`);
            if (data.success) {
                const count = data.count;
                el.textContent = count >= 50 ? '50+ (max)' : count;
                el.className = count > 0 ? 'text-green-400 font-medium' : 'text-red-400 font-medium';
            } else {
                el.textContent = 'Error';
                el.className = 'text-red-400 font-medium';
            }
        })
        .catch(() => {
            document.getElementById(`${type}-count`).textContent = 'Error';
        });
}

function fetchTarget(type) {
    const btn = document.getElementById(`btn-${type}`);
    const resultArea = document.getElementById('result-area');
    const errorArea = document.getElementById('error-area');
    const loadingArea = document.getElementById('loading-area');
    const limit = document.getElementById('target-limit').value;

    btn.disabled = true;
    resultArea.classList.add('hidden');
    errorArea.classList.add('hidden');
    loadingArea.classList.remove('hidden');

    document.getElementById('loading-text').textContent = type === 'easy' ? 'Finding easy targets...' : 'Finding good targets...';

    fetch(`/target-finder/target/${type}?limit=${limit}`)
        .then(res => res.json())
        .then(data => {
            loadingArea.classList.add('hidden');

            if (data.success) {
                const targetsList = document.getElementById('targets-list');
                targetsList.innerHTML = '';

                data.targets.forEach((target, index) => {
                    const div = document.createElement('div');
                    div.className = 'p-4 bg-gray-700/50 rounded-lg border border-gray-600';
                    div.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 text-sm">#${index + 1}</span>
                                    <div class="text-lg font-semibold text-white">${target.name}</div>
                                    <span class="text-gray-500">[${target.player_id}]</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400 mt-1">
                                    <span>Level <span class="text-white font-medium">${target.level}</span></span>
                                    <span>FF <span class="text-green-400 font-medium">${target.fair_fight.toFixed(2)}</span></span>
                                    <span>Inactive: <span class="text-yellow-400 font-medium">${target.inactiveFormatted}</span></span>
                                    <span>Est: <span class="text-blue-400 font-medium">${target.estStats}</span></span>
                                </div>
                            </div>
                            <a href="${target.attackUrl}" target="_blank" class="ml-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium whitespace-nowrap">
                                Attack
                            </a>
                        </div>
                    `;
                    targetsList.appendChild(div);
                });

                resultArea.classList.remove('hidden');
            } else {
                document.getElementById('error-message').textContent = data.error;
                errorArea.classList.remove('hidden');
            }
        })
        .catch(err => {
            loadingArea.classList.add('hidden');
            document.getElementById('error-message').textContent = 'Request failed: ' + err.message;
            errorArea.classList.remove('hidden');
        })
        .finally(() => {
            btn.disabled = false;
        });
}
</script>
@endpush