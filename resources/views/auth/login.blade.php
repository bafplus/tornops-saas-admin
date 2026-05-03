<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - TornOps</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-6">Admin Login</h1>
        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="/master-login">
            @csrf
            <div class="mb-4">
                <label class="block mb-2">Master Key</label>
                <input type="password" name="master_key" class="w-full border p-2 rounded" required>
            </div>
            <button type="submit" class="w-full bg-red-500 text-white p-2 rounded hover:bg-red-600">Login</button>
        </form>
    </div>
</body>
</html>
