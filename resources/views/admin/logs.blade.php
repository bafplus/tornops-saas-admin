@extends('layouts.app')

@section('title', 'Logs - TornOps')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <a href="/admin" class="text-gray-400 hover:text-white text-sm">← Back to Admin</a>
        <h1 class="text-2xl font-bold mt-2">Recent Log Entries</h1>
        <p class="text-gray-400">Last 100 lines from laravel.log</p>
    </div>

    <div class="bg-gray-900 rounded-lg border border-gray-700 p-4 font-mono text-xs overflow-x-auto">
        @if(empty($lines))
            <p class="text-gray-500">No log entries found.</p>
        @else
            @foreach($lines as $line)
                @php
                    $class = 'text-gray-400';
                    if (str_contains($line, 'ERROR')) $class = 'text-red-400';
                    elseif (str_contains($line, 'WARNING')) $class = 'text-yellow-400';
                    elseif (str_contains($line, 'INFO')) $class = 'text-green-400';
                @endphp
                <div class="{{ $class }} whitespace-pre-wrap break-all">{{ $line }}</div>
            @endforeach
        @endif
    </div>
</div>
@endsection
