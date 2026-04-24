@extends('layouts.app')

@section('title', 'Accept Invitation - TornOps')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-900">
    <div class="bg-gray-800 p-8 rounded-lg border border-gray-700 w-full max-w-md">
        <h1 class="text-2xl font-bold text-white mb-2">Accept Invitation</h1>
        <p class="text-gray-400 mb-6">
            You've been invited to join TornOps, <strong>{{ $user->name }}</strong>.
        </p>
        <p class="text-gray-400 text-sm mb-6">
            Player ID: <span class="font-mono text-blue-400">{{ $user->torn_player_id }}</span>
        </p>

        @if(session('error'))
            <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/invite/{{ $token }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-400 mb-2">Password</label>
                <input type="password" name="password" required
                       class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white font-semibold">
                Set Password & Login
            </button>
        </form>
    </div>
</div>
@endsection