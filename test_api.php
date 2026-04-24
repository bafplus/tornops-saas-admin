<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = new App\Services\TornApiService();
$result = $api->getFactionCrimes(55742);

echo "Result:\n";
var_dump($result);
