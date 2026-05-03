<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TornOps">
    <title>@yield('title', 'TornOps')</title>
    <link rel="icon" type="image/png" href="/images/tornops-shield-background.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/tornops-shield-background.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/tornops-shield-background.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/tornops-shield-background.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen" data-travel-method="{{ $travelMethod ?? 1 }}">
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
<div class="flex items-center space-x-8">
            <a href="/" class="flex items-center">
                <img src="/images/tornops-shield-text-transparant.png" alt="TornOps" class="h-10">
            </a>
            @auth
            <div class="hidden md:flex items-center space-x-4">
                <a href="/dashboard" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>

                <div class="relative group">
                    <button class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 flex items-center gap-1">
                        Faction
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                        <a href="/members" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('members') ? 'bg-gray-700 text-white' : '' }}">Members</a>
                        <a href="/wars" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('wars*') ? 'bg-gray-700 text-white' : '' }}">Ranked Wars</a>
                        <a href="/organized-crimes" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('organized-crimes*') ? 'bg-gray-700 text-white' : '' }}">Organized Crimes</a>
                    </div>
                </div>

                <div class="relative group">
                    <button class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 flex items-center gap-1">
                        Tools
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                        <a href="/gym" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('gym') ? 'bg-gray-700 text-white' : '' }}">Gym Assistant</a>
                        <a href="/merits" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('merits*') ? 'bg-gray-700 text-white' : '' }}">Merits</a>
                        <a href="/stocks" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('stocks*') ? 'bg-gray-700 text-white' : '' }}">Stocks</a>
                        <a href="/jumps" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('jumps*') ? 'bg-gray-700 text-white' : '' }}">Jump Helper</a>
                        <a href="/target-finder" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('target-finder') ? 'bg-gray-700 text-white' : '' }}">Target Finder</a>
                    </div>
                </div>
            </div>
            @endauth
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 text-gray-300 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                @auth
                <a href="/settings" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Settings</a>
                @if(auth()->user()->is_admin)
                <a href="/admin" class="px-3 py-2 rounded-md text-purple-300 hover:text-white hover:bg-gray-700">Admin</a>
                @endif
                <form action="/logout" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                        Logout
                    </button>
                </form>
                @else
                <a href="/login" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Login</a>
                @endauth
            </div>
        </div>
        
<!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden pb-4">
        <div class="flex flex-col space-y-2">
            @auth
            <a href="/dashboard" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>

            <div class="py-2">
                <div class="text-gray-500 text-sm px-3">Faction</div>
                <a href="/members" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('members') ? 'bg-gray-700 text-white' : '' }}">- Members</a>
                <a href="/wars" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('wars*') ? 'bg-gray-700 text-white' : '' }}">- Ranked Wars</a>
            </div>

            <div class="py-2">
                <div class="text-gray-500 text-sm px-3">Tools</div>
                <a href="/gym" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('gym') ? 'bg-gray-700 text-white' : '' }}">- Gym Assistant</a>
                <a href="/merits" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('merits*') ? 'bg-gray-700 text-white' : '' }}">- Merits</a>
                <a href="/stocks" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('stocks*') ? 'bg-gray-700 text-white' : '' }}">- Stocks</a>
                <a href="/jumps" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('jumps*') ? 'bg-gray-700 text-white' : '' }}">- Jump Helper</a>
                <a href="/target-finder" class="block px-6 py-2 text-gray-300 hover:bg-gray-700 {{ request()->is('target-finder') ? 'bg-gray-700 text-white' : '' }}">- Target Finder</a>
            </div>

            @endauth

            @auth
                <a href="/settings" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Settings</a>
                @if(auth()->user()->is_admin)
                <a href="/admin" class="px-3 py-2 rounded-md text-purple-300 hover:text-white hover:bg-gray-700">Admin</a>
                @endif
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Logout</button>
                </form>
                @else
                <a href="/login" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Login</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
