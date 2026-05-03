<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oc = App\Models\OrganizedCrime::whereStatus('successful')->first();

echo "OC: " . $oc->name . "\n";
echo "Attributes:\n";
print_r($oc->getAttributes());
echo "\nRewards cast: ";
var_dump($oc->rewards);
