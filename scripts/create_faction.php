#!/usr/bin/env php
<?php

$slug = $argv[1] ?? null;
$masterKey = $argv[2] ?? null;
$tornApiKey = $argv[3] ?? null;

if (!$slug || !$masterKey) {
    echo "Usage: create_faction.php <slug> <masterKey> [tornApiKey]\n";
    exit(1);
}

$basePath = '/home/server/tornops-instances';
$domain = 'tornops.net';
$instancePath = "{$basePath}/{$slug}";

echo "[START] Creating faction: {$slug}\n";

// 1. Copy source code
echo "[1/7] Cloning source code...\n";
if (is_dir($instancePath)) {
    echo "  Directory exists, removing...\n";
    exec("rm -rf {$instancePath}");
}
mkdir("/{$instancePath}", 0755, true);
exec("cd {$instancePath} && git clone https://github.com/bafplus/tornops.git . 2>&1", $out);
echo "  Done\n";

// 2. Setup directories
echo "[2/7] Setting up directories...\n";
exec("mkdir -p {$instancePath}/storage");
exec("mkdir -p {$instancePath}/bootstrap/cache");
exec("mkdir -p {$instancePath}/data");
exec("chmod -R 777 {$instancePath}/storage {$instancePath}/bootstrap/cache {$instancePath}/data");
exec("ln -sf {$instancePath}/storage {$instancePath}/data/storage");
echo "  Done\n";

// 3. Create .env
echo "[3/7] Creating .env...\n";
$dbPath = "{$instancePath}/storage/database.sqlite";
$apiKey = $tornApiKey ?: 'demo';
$appKey = 'base64:' . base64_encode(random_bytes(32));
$env = "APP_NAME=TornOps-{$slug}\n";
$env .= "APP_ENV=production\n";
$env .= "APP_DEBUG=false\n";
$env .= "LOG_CHANNEL=stack\n";
$env .= "LOG_LEVEL=warning\n";
$env .= "DB_CONNECTION=sqlite\n";
$env .= "DB_DATABASE={$dbPath}\n";
$env .= "MASTER_KEY={$masterKey}\n";
$env .= "TORN_API_KEY={$apiKey}\n";
$env .= "SESSION_DRIVER=file\n";
$env .= "CACHE_STORE=file\n";
$env .= "APP_KEY={$appKey}\n";
file_put_contents("{$instancePath}/.env", $env);
exec("chmod 644 {$instancePath}/.env");
echo "  APP_KEY set\n";

// 4. Fix data path
echo "[4/7] Fixing data path...\n";
$viewPath = "{$instancePath}/resources/views/setup/index.blade.php";
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    $content = str_replace('/data', $instancePath . '/data', $content);
    file_put_contents($viewPath, $content);
    echo "  Fixed\n";
}

// 5. Install dependencies
echo "[5/7] Installing composer dependencies...\n";
exec("cd {$instancePath} && composer install --no-interaction 2>&1", $out);
echo "  Done\n";

// 6. Setup database
echo "[6/7] Setting up database...\n";
touch($dbPath);
chmod($dbPath, 0666);
exec("cd {$instancePath} && php artisan config:clear 2>&1");
exec("cd {$instancePath} && php artisan migrate --force 2>&1", $out);
echo "  Done\n";
exec("cd {$instancePath} && php artisan jobs:seed 2>&1", $out);

// 7. Find port and start server
echo "[7/7] Starting PHP server...\n";
exec("ss -tlnp | grep php", $out);
$usedPorts = [];
foreach ($out as $line) {
    if (preg_match('/:(\d+)/', $line, $m)) {
        $usedPorts[] = (int)$m[1];
    }
}
$port = 8082;
while (in_array($port, $usedPorts)) {
    $port++;
}
exec("cd {$instancePath} && nohup php -S 0.0.0.0:{$port} -t public > /tmp/tornops-{$slug}.log 2>&1 &");
echo "  Started on port {$port}\n";

echo "[DONE] Faction {$slug} created!\n";
echo "URL: https://{$slug}.{$domain}\n";
echo "PORT: {$port}\n";

// 8. Add Cloudflare DNS
echo "[8/8] Adding Cloudflare DNS...\n";
$cfEmail = 'bart.cockheyt@gmail.com';
$cfKey = 'd888ebd7a3d4da09d50f35b1c6dea49d92f96';
$zoneId = '677fb6d5519e5c230f1de82aba4e8cb2';
$tunnelId = 'b5999d7d-67f0-461f-9513-7b8dcb15766d';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Auth-Email: {$cfEmail}",
    "X-Auth-Key: {$cfKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'type' => 'CNAME',
    'name' => $slug,
    'content' => "{$tunnelId}.cfargotunnel.com",
    'proxied' => true
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);
echo "  DNS added\n";

// 9. Add Cloudflare Tunnel route
echo "[9/9] Adding Cloudflare Tunnel route...\n";
$accountId = '7ea602d1cd797bf3cb34885c9d240114';

// Get current config
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$accountId}/cfd_tunnel/{$tunnelId}/configurations");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Email: {$cfEmail}", "X-Auth-Key: {$cfKey}", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
curl_close($ch);
$tunnel = json_decode($resp, true);
$ingress = $tunnel['result']['config']['ingress'] ?? [];

// Add new route after tornops.net
$newIngress = [];
foreach ($ingress as $r) {
    if (isset($r['service']) && $r['service'] === 'http_status:404') continue;
    $newIngress[] = $r;
    if (isset($r['hostname']) && $r['hostname'] === 'tornops.net') {
        $newIngress[] = ['hostname' => "{$slug}.{$domain}", 'service' => "http://217.154.154.7:{$port}"];
    }
}
$newIngress[] = ['service' => 'http_status:404'];

// Save config
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$accountId}/cfd_tunnel/{$tunnelId}/configurations");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Email: {$cfEmail}", "X-Auth-Key: {$cfKey}", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['config' => ['ingress' => $newIngress, 'warp-routing' => ['enabled' => false]]]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code === 200) {
    echo "  Tunnel route added\n";
} else {
    echo "  Tunnel failed ({$code})\n";
}