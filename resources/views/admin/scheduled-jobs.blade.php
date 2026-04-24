@extends('layouts.app')

@section('title', 'Scheduled Jobs - TornOps')

@section('content')
<?php
function parseCronToPreset($cron) {
    if (!$cron) return '';
    $parts = explode(' ', $cron);
    $min = $parts[0] ?? '';
    $hr = $parts[1] ?? '';
    
    if ($min === '*' && $hr === '*') return 'every_minute';
    
    if ($min === '1-59/2') return 'every_odd_minute';
    if ($min === '2-58/2') return 'every_even_minute';
    
    if (str_starts_with($min, '*/')) {
        $val = substr($min, 2);
        if ($hr === '*') return 'every_' . $val . '_min';
    }
    
    if ($min === '0' && $hr === '*') return 'every_hour';
    
    if ($min === '0' && str_starts_with($hr, '*/')) {
        $val = substr($hr, 2);
        return 'every_' . $val . '_hour';
    }
    
    if ($min === '0' && is_numeric($hr)) return 'every_day';
    
    return 'custom';
}

function parseCronToCustom($cron) {
    if (!$cron) return ['value' => '', 'unit' => 'minutes', 'hour' => '', 'minute' => ''];
    $parts = explode(' ', $cron);
    $min = $parts[0] ?? '';
    $hr = $parts[1] ?? '';
    
    if (str_starts_with($min, '*/')) {
        return ['value' => substr($min, 2), 'unit' => 'minutes', 'hour' => '', 'minute' => ''];
    }
    if ($min === '*') {
        return ['value' => '', 'unit' => 'minutes', 'hour' => '', 'minute' => ''];
    }
    
    if ($min === '0' && str_starts_with($hr, '*/')) {
        return ['value' => substr($hr, 2), 'unit' => 'hours', 'hour' => '', 'minute' => ''];
    }
    
    if ($min === '0' && is_numeric($hr)) {
        return ['value' => '1', 'unit' => 'days', 'hour' => $hr, 'minute' => $min];
    }
    
    return ['value' => '', 'unit' => 'minutes', 'hour' => '', 'minute' => ''];
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Scheduled Jobs</h1>
        <form action="/admin/scheduled-jobs/seed" method="POST">
            @csrf
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded text-white text-sm">
                Reset to Defaults
            </button>
        </form>
    </div>

    @if(session('status'))
        <div class="bg-green-900/50 border border-green-700 text-green-400 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Command</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Description</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">API Calls</th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">Enabled</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Schedule (Peace)</th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">War Only</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Schedule (War)</th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @foreach($jobs as $job)
                <?php
                    $cronPreset = parseCronToPreset($job->cron_expression);
                    $cronCustom = parseCronToCustom($job->cron_expression);
                    $warPreset = $job->war_mode_only ? parseCronToPreset($job->war_cron) : '';
                    $warCustom = $job->war_mode_only ? parseCronToCustom($job->war_cron) : ['value' => '', 'unit' => 'minutes'];
                ?>
                <tr class="hover:bg-gray-750">
                    <form action="/admin/scheduled-jobs/{{ $job->id }}" method="POST">
                        @csrf
                        @method('PUT')
                        <td class="px-3 py-3">
                            <code class="text-sm text-yellow-400">{{ $job->command }}</code>
                        </td>
                        <td class="px-3 py-3 text-gray-300 text-sm">
                            {{ $job->description }}
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-400">
                            @if($job->api_info)
                                <div>{{ $job->api_info }}</div>
                                <div class="text-yellow-400 font-semibold">~{{ $job->api_est }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="enabled" value="1" {{ $job->enabled ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600">
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex gap-1 items-center">
                                <select name="cron_custom_preset" onchange="toggleCustom(this, 'cron')" 
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-36">
                                    <option value="every_minute" {{ $cronPreset == 'every_minute' ? 'selected' : '' }}>Every minute</option>
                                    <option value="every_odd_minute" {{ $cronPreset == 'every_odd_minute' ? 'selected' : '' }}>Every odd minute</option>
                                    <option value="every_even_minute" {{ $cronPreset == 'every_even_minute' ? 'selected' : '' }}>Every even minute (2,4,6...)</option>
                                    <option value="every_5_min" {{ $cronPreset == 'every_5_min' ? 'selected' : '' }}>Every 5 minutes</option>
                                    <option value="every_10_min" {{ $cronPreset == 'every_10_min' ? 'selected' : '' }}>Every 10 minutes</option>
                                    <option value="every_15_min" {{ $cronPreset == 'every_15_min' ? 'selected' : '' }}>Every 15 minutes</option>
                                    <option value="every_30_min" {{ $cronPreset == 'every_30_min' ? 'selected' : '' }}>Every 30 minutes</option>
                                    <option value="every_hour" {{ $cronPreset == 'every_hour' ? 'selected' : '' }}>Every hour</option>
                                    <option value="every_2_hour" {{ $cronPreset == 'every_2_hour' ? 'selected' : '' }}>Every 2 hours</option>
                                    <option value="every_6_hour" {{ $cronPreset == 'every_6_hour' ? 'selected' : '' }}>Every 6 hours</option>
                                    <option value="every_12_hour" {{ $cronPreset == 'every_12_hour' ? 'selected' : '' }}>Every 12 hours</option>
                                    <option value="every_day_00" {{ $cronPreset == 'every_day_00' ? 'selected' : '' }}>Daily at midnight</option>
                                    <option value="every_day_06" {{ $cronPreset == 'every_day_06' ? 'selected' : '' }}>Daily at 6 AM</option>
                                    <option value="every_day_12" {{ $cronPreset == 'every_day_12' ? 'selected' : '' }}>Daily at noon</option>
                                    <option value="every_day_18" {{ $cronPreset == 'every_day_18' ? 'selected' : '' }}>Daily at 6 PM</option>
                                    <option value="custom" {{ $cronPreset == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <input type="number" name="cron_custom_value" min="1" max="999" value="{{ $cronCustom['value'] }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center {{ !in_array($cronPreset, ['custom']) ? 'hidden' : '' }}"
                                       placeholder="1">
                                <select name="cron_custom_unit" 
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-20 {{ !in_array($cronPreset, ['custom']) ? 'hidden' : '' }}">
                                    <option value="minutes" {{ $cronCustom['unit'] == 'minutes' ? 'selected' : '' }}>min</option>
                                    <option value="hours" {{ $cronCustom['unit'] == 'hours' ? 'selected' : '' }}>hours</option>
                                    <option value="days" {{ $cronCustom['unit'] == 'days' ? 'selected' : '' }}>days</option>
                                </select>
                                <input type="number" name="cron_custom_hour" min="0" max="23" value="{{ $cronCustom['hour'] ?? '' }}" 
                                       class="w-14 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center {{ $cronPreset != 'custom' ? 'hidden' : '' }}"
                                       placeholder="Hr">
                                <span class="text-gray-500 {{ $cronPreset != 'custom' ? 'hidden' : '' }}">:</span>
                                <input type="number" name="cron_custom_minute" min="0" max="59" value="{{ $cronCustom['minute'] ?? '' }}" 
                                       class="w-14 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center {{ $cronPreset != 'custom' ? 'hidden' : '' }}"
                                       placeholder="Min">
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="war_mode_only" value="1" {{ $job->war_mode_only ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600" onchange="toggleWarRow(this)">
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex gap-1 items-center">
                                <select name="war_custom_preset" onchange="toggleCustom(this, 'war')" 
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-36 {{ !$job->war_mode_only ? 'opacity-50' : '' }}"
                                        {{ !$job->war_mode_only ? 'disabled' : '' }}>
                                    <option value="every_minute" {{ $warPreset == 'every_minute' ? 'selected' : '' }}>Every minute</option>
                                    <option value="every_1_min" {{ $warPreset == 'every_1_min' ? 'selected' : '' }}>Every 1 minute</option>
                                    <option value="every_2_min" {{ $warPreset == 'every_2_min' ? 'selected' : '' }}>Every 2 minutes</option>
                                    <option value="every_5_min" {{ $warPreset == 'every_5_min' ? 'selected' : '' }}>Every 5 minutes</option>
                                    <option value="every_10_min" {{ $warPreset == 'every_10_min' ? 'selected' : '' }}>Every 10 minutes</option>
                                    <option value="custom" {{ $warPreset == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <input type="number" name="war_custom_value" min="1" max="999" value="{{ $warCustom['value'] }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center {{ !$job->war_mode_only ? 'hidden' : '' }} {{ $warPreset != 'custom' ? 'hidden' : '' }}"
                                       placeholder="1" {{ !$job->war_mode_only ? 'disabled' : '' }}>
                                <select name="war_custom_unit" 
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-20 {{ !$job->war_mode_only ? 'hidden' : '' }} {{ $warPreset != 'custom' ? 'hidden' : '' }}"
                                        {{ !$job->war_mode_only ? 'disabled' : '' }}>
                                    <option value="minutes" {{ $warCustom['unit'] == 'minutes' ? 'selected' : '' }}>min</option>
                                    <option value="hours" {{ $warCustom['unit'] == 'hours' ? 'selected' : '' }}>hours</option>
                                </select>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white text-sm">
                                Save
                            </button>
                        </td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700 text-sm text-gray-400">
        <p><span class="text-gray-300 font-semibold">War Only</span> = During a war, only these jobs run</p>
    </div>
</div>

<script>
function toggleCustom(select, type) {
    const row = select.closest('tr');
    const valueInput = row.querySelector(`input[name="${type}_custom_value"]`);
    const unitSelect = row.querySelector(`select[name="${type}_custom_unit"]`);
    const hourInput = row.querySelector(`input[name="${type}_custom_hour"]`);
    const minInput = row.querySelector(`input[name="${type}_custom_minute"]`);
    const colonSpan = row.querySelector(`span.text-gray-500`);
    
    if (select.value === 'custom') {
        valueInput.classList.remove('hidden');
        unitSelect.classList.remove('hidden');
        hourInput.classList.remove('hidden');
        minInput.classList.remove('hidden');
        colonSpan.classList.remove('hidden');
    } else {
        valueInput.classList.add('hidden');
        unitSelect.classList.add('hidden');
        hourInput.classList.add('hidden');
        minInput.classList.add('hidden');
        colonSpan.classList.add('hidden');
    }
}

function toggleWarRow(checkbox) {
    const row = checkbox.closest('tr');
    const warSelect = row.querySelector('select[name^="war_"]');
    const warValue = row.querySelector('input[name="war_custom_value"]');
    const warUnit = row.querySelector('select[name="war_custom_unit"]');
    
    if (checkbox.checked) {
        warSelect.disabled = false;
        warSelect.classList.remove('opacity-50');
        warValue.classList.remove('hidden');
        warUnit.classList.remove('hidden');
    } else {
        warSelect.disabled = true;
        warSelect.classList.add('opacity-50');
        warValue.classList.add('hidden');
        warValue.value = '';
        warUnit.classList.add('hidden');
    }
}
</script>
@endsection
