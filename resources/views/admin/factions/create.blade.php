<!DOCTYPE html>
<html>
<head>
    <title>Create Faction - TornOps Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto">
            <a href="/admin/factions" class="text-xl font-bold">TornOps Admin</a>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <div class="bg-white rounded-lg shadow p-6 max-w-lg">
            <h2 class="text-2xl font-bold mb-6">Create New Faction</h2>

            <form method="POST" action="/admin/factions">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2">Faction Name</label>
                    <input type="text" name="name" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Slug (URL-friendly)</label>
                    <input type="text" name="slug" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-2">Torn Faction ID</label>
                    <input type="number" name="torn_faction_id" class="w-full border p-2 rounded" required>
                </div>

                <hr class="my-4">

                <h3 class="text-lg font-semibold mb-3">Subscription</h3>
                <div class="mb-4">
                    <label class="block mb-2">Type</label>
                    <select name="subscription_type" class="w-full border p-2 rounded" id="sub-type">
                        <option value="free">Free</option>
                        <option value="trial">Trial (1 week)</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="mb-4" id="payment-fields">
                    <label class="block mb-2">Payment Item</label>
                    <input type="text" name="payment_item" value="xanax" class="w-full border p-2 rounded">
                </div>
                <div class="mb-4" id="amount-fields">
                    <label class="block mb-2">Payment Amount (per week)</label>
                    <input type="number" name="payment_amount" value="1" min="1" class="w-full border p-2 rounded">
                </div>

                <button type="submit" class="w-full bg-red-500 text-white p-2 rounded">Create Faction</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('sub-type').addEventListener('change', function() {
        var isPaid = this.value === 'paid';
        document.getElementById('payment-fields').style.display = isPaid ? '' : 'none';
        document.getElementById('amount-fields').style.display = isPaid ? '' : 'none';
    });
    document.getElementById('sub-type').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
