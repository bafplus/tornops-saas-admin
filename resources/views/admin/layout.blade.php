<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - TornOps SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/admin" class="text-xl font-bold text-blue-400">TornOps Admin</a>
                    <div class="flex space-x-4">
                        <a href="/admin/factions" class="text-gray-300 hover:text-white">Factions</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-400 text-sm">Logged in as admin</span>
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-white text-sm">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
</body>
</html>