@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Merit Planner</h1>
            <p class="text-gray-400 mt-1">Plan and track your merit allocation</p>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded mb-6">
            {{ session('error') }} - <a href="{{ route('merits.fetch') }}" class="underline hover:text-white">Try again</a>
        </div>
    @endif

    @if(isset($fetchError))
        <div class="bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded mb-6">
            {{ $fetchError }} - <a href="{{ route('merits.fetch') }}" class="underline hover:text-white">Try again</a>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-900/50 border border-green-700 text-green-200 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(!$hasData && !isset($fetchError))
        <div class="bg-gray-800 rounded-lg p-8 text-center border border-gray-700">
            <h2 class="text-xl font-semibold text-white mb-2">Loading Merit Data...</h2>
            <p class="text-gray-400">Fetching your merits from Torn.</p>
        </div>
    @else
        <div class="bg-gray-800 rounded-lg p-6 mb-6 border border-gray-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400" id="summary-available">{{ $availablePoints }}</div>
                        <div class="text-sm text-gray-400">Available</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400" id="summary-used">{{ $usedPoints }}</div>
                        <div class="text-sm text-gray-400">Used</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400" id="summary-planned-cost">{{ $totalPlannedCost }}</div>
                        <div class="text-sm text-gray-400">Planned Cost</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $extraNeeded > 0 ? 'text-red-400' : 'text-green-400' }}" id="summary-extra-needed">
                            {{ $extraNeeded > 0 ? '+' . $extraNeeded : '0' }}
                        </div>
                        <div class="text-sm text-gray-400">Extra Needed</div>
                    </div>
                </div>
                <div class="flex space-x-3 mt-4 md:mt-0">
                    <form action="{{ route('merits.fetch') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium">
                            Refresh from API
                        </button>
                    </form>
                    <form action="{{ route('merits.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium">
                            Reset to Current
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @foreach($groupedMerits as $categoryName => $merits)
            @if(count($merits) > 0)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        {{ $categoryName }}
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($merits as $merit)
                            @php $meritId = str_replace(' ', '_', $merit['name']); @endphp
                            <div class="bg-gray-800 rounded-lg p-3 border border-gray-700" id="merit-{{ $meritId }}">
                                <div class="mb-2">
                                    <h3 class="text-base font-semibold text-white">{{ $merit['name'] }}</h3>
                                    <p class="text-xs text-gray-400">{{ $merit['description'] }}</p>
                                </div>
                                
                                <div class="flex items-center text-xs mb-1">
                                    <span class="w-14 text-gray-300">Current</span>
                                    <span class="w-8 text-right text-gray-400 mr-2 current-level" id="current-level-{{ $meritId }}">{{ $merit['current_level'] }}/10</span>
                                    <div class="w-4"></div>
                                    <div class="w-56 flex" id="current-bar-{{ $meritId }}">
                                        @for($i = 1; $i <= 10; $i++)
                                            <div class="flex-1 h-3 rounded-sm mr-px current-seg-{{ $meritId }} {{ $i <= $merit['current_level'] ? 'bg-green-500' : 'bg-gray-700' }}"></div>
                                        @endfor
                                    </div>
                                    <div class="w-4"></div>
                                    <span class="ml-2 text-right text-green-400 truncate min-w-[60px]" title="{{ $merit['current_bonus'] }}" id="current-bonus-{{ $meritId }}">{{ $merit['current_bonus'] }}</span>
                                </div>
                                
                                <div class="flex items-center text-xs">
                                    <span class="w-14 text-gray-300">Planned</span>
                                    <span class="w-8 text-right text-gray-400 mr-2 planned-level" id="planned-level-{{ $meritId }}">{{ $merit['planned_level'] }}/10</span>
                                    <button 
                                        type="button"
                                        onclick="updateMerit('{{ $merit['name'] }}', -1)"
                                        class="w-4 h-4 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white text-xs btn-minus-{{ $meritId }}"
                                        {{ $merit['planned_level'] <= 0 ? 'disabled' : '' }}
                                    >-</button>
                                    <div class="w-56 flex" id="planned-bar-{{ $meritId }}">
                                        @for($i = 1; $i <= 10; $i++)
                                            <div 
                                                class="flex-1 h-3 rounded-sm mr-px cursor-pointer transition-colors planned-seg-{{ $meritId }} {{ $i <= $merit['planned_level'] ? 'bg-purple-500' : 'bg-gray-700 hover:bg-gray-600' }}"
                                                onclick="updateMerit('{{ $merit['name'] }}', {{ $i }})"
                                            ></div>
                                        @endfor
                                    </div>
                                    <button 
                                        type="button"
                                        onclick="updateMerit('{{ $merit['name'] }}', 1)"
                                        class="w-4 h-4 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white text-xs btn-plus-{{ $meritId }}"
                                        {{ $merit['planned_level'] >= 10 ? 'disabled' : '' }}
                                    >+</button>
                                    <span class="ml-2 text-right text-purple-400 truncate min-w-[60px] planned-bonus" title="{{ $merit['planned_bonus'] }}" id="planned-bonus-{{ $meritId }}">{{ $merit['planned_bonus'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

@push('scripts')
<script>
function updateMerit(meritName, change) {
    const meritId = meritName.replace(/ /g, '_');
    
    // Get buttons
    const minusBtn = document.querySelector(`.btn-minus-${meritId}`);
    const plusBtn = document.querySelector(`.btn-plus-${meritId}`);
    
    // Disable buttons during update
    if (minusBtn) minusBtn.disabled = true;
    if (plusBtn) plusBtn.disabled = true;
    
    fetch('{{ route('merits.update') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            merit_name: meritName,
            planned_level: change
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            alert(data.error);
            if (minusBtn) minusBtn.disabled = false;
            if (plusBtn) plusBtn.disabled = false;
            return;
        }
        
        if (!data.success) {
            alert('Unknown error occurred');
            if (minusBtn) minusBtn.disabled = false;
            if (plusBtn) plusBtn.disabled = false;
            return;
        }
        
        // Update this merit's planned row
        document.getElementById(`planned-level-${meritId}`).textContent = data.planned_level + '/10';
        document.getElementById(`planned-bonus-${meritId}`).textContent = data.planned_bonus;
        
        // Update planned bar segments
        const plannedSegs = document.querySelectorAll(`.planned-seg-${meritId}`);
        plannedSegs.forEach((seg, idx) => {
            const level = idx + 1;
            if (level <= data.planned_level) {
                seg.classList.remove('bg-gray-700', 'hover:bg-gray-600');
                seg.classList.add('bg-purple-500');
            } else {
                seg.classList.remove('bg-purple-500');
                seg.classList.add('bg-gray-700', 'hover:bg-gray-600');
            }
        });
        
        // Update button states
        if (minusBtn) minusBtn.disabled = data.planned_level <= 0;
        if (plusBtn) plusBtn.disabled = data.planned_level >= 10;
        
        // Update summary
        const summaryPlannedCost = document.getElementById('summary-planned-cost');
        if (summaryPlannedCost) summaryPlannedCost.textContent = data.total_planned_cost;
        
        const summaryAvailable = document.getElementById('summary-available');
        if (summaryAvailable) summaryAvailable.textContent = data.available_points;
        
        const summaryUsed = document.getElementById('summary-used');
        if (summaryUsed) summaryUsed.textContent = data.used_points;
        
        const extraEl = document.getElementById('summary-extra-needed');
        if (extraEl) {
            extraEl.textContent = data.extra_needed > 0 ? '+' + data.extra_needed : '0';
            extraEl.classList.remove('text-red-400', 'text-green-400');
            extraEl.classList.add(data.extra_needed > 0 ? 'text-red-400' : 'text-green-400');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update merit');
        if (minusBtn) minusBtn.disabled = false;
        if (plusBtn) plusBtn.disabled = false;
    });
}
</script>
@endpush
@endsection
