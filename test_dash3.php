<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = new App\Http\Controllers\DashboardController();
$response = $controller->index();

$content = $response->render();

echo "Response contains 'in OC': " . (strpos($content, 'in OC') !== false ? 'YES' : 'NO') . "\n";

if (preg_match('/Members Not in OC/', $content)) {
    echo "Found OC section\n";
} else {
    echo "OC section NOT found\n";
    echo "Last 500 chars: " . substr($content, -500) . "\n";
}
