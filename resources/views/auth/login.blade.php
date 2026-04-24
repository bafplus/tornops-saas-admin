@extends('layouts.app')

@section('title', 'Login - TornOps')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 w-full max-w-md">
        <img src="/images/tornops-shield-text-transparant.png" alt="TornOps" class="h-16 mx-auto mb-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Login to TornOps</h1>
        
        @if($errors->any())
            <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('status'))
            <div class="bg-green-900/50 border border-green-700 text-green-400 px-4 py-3 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif
        
        <form action="/login" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-400 mb-2">Username</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-400 mb-2">Password</label>
                <input type="password" name="password" required
                       class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-2 rounded text-white font-semibold">
                Login
            </button>
        </form>
    </div>
</div>
@endsection