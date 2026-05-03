<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$planning = App\Models\OrganizedCrime::where('status', 'planning')->first();
$successful = App\Models\OrganizedCrime::whereIn('status', ['successful', 'success'])->first();

echo "=== PLANNING OC ===\n";
echo json_encode($planning, JSON_PRETTY_PRINT) . "\n\n";

if ($planning) {
    echo "=== PLANNING SLOTS ===\n";
    echo json_encode($planning->slots, JSON_PRETTY_PRINT) . "\n\n";
}

echo "=== SUCCESSFUL OC ===\n";
echo json_encode($successful, JSON_PRETTY_PRINT) . "\n\n";

if ($successful) {
    echo "=== SUCCESSFUL SLOTS ===\n";
    echo json_encode($successful->slots, JSON_PRETTY_PRINT) . "\n\n";
}
