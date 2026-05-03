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

// 1. Create instance folder
echo "[1/6] Creating folder...\n";
if (is_dir($instancePath)) {
    exec("rm -rf {$instancePath}");
}
mkdir($instancePath, 0755, true);
mkdir("{$instancePath}/storage", 0755, true);
mkdir("{$instancePath}/data", 0755, true);
echo "  Done\n";

// 2. Create .env file
echo "[2/6] Creating .env...\n";
$apiKey = $tornApiKey ?: 'demo';
$env = "TORN_API_KEY={$apiKey}\n";
$env .= "MASTER_KEY={$masterKey}\n";
$env .= "APP_ENV=production\n";
$env .= "APP_DEBUG=false\n";
file_put_contents("{$instancePath}/.env", $env);
exec("chmod 644 {$instancePath}/.env");
echo "  Done\n";

// 3. Create docker-compose.yml
echo "[3/6] Creating docker-compose.yml...\n";
$compose = "services:
  tornops:
    image: ghcr.io/bafplus/tornops/tornops:latest
    restart: unless-stopped
    container_name: {$slug}
    ports:
      - \"8080:80\"
    volumes:
      - \"./storage:/app/storage\"
      - \"./data:/data\"
    env_file:
      - \".env\"
";
file_put_contents("{$instancePath}/docker-compose.yml", $compose);
echo "  Done\n";

// 4. Find available port
echo "[4/6] Finding available port...\n";
exec("ss -tlnp | grep ':'", $out);
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
echo "  Using port {$port}\n";

// 5. Update compose with port and start container
echo "[5/6] Starting Docker container...\n";
$composeContent = str_replace('"8080:80"', '"' . $port . ':80"', file_get_contents("{$instancePath}/docker-compose.yml"));
file_put_contents("{$instancePath}/docker-compose.yml", $composeContent);
chdir($instancePath);
exec("docker-compose up -d 2>&1", $out);
echo "  " . implode("\n  ", $out) . "\n";

// 6. Add Cloudflare DNS
echo "[6/6] Adding Cloudflare DNS...\n";
$cfEmail = 'bart.cockheyt@gmail.com';
$cfKey = 'd888ebd7a3d4da09d50f35b1c6dea49d92f96';
$zoneId = '677fb6d5519e5c230f1de82aba4e8cb2';
$tunnelId = 'b726eb72-ac86-40e7-9b2b-266943fd2110';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Email: {$cfEmail}", "X-Auth-Key: {$cfKey}", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['type' => 'CNAME', 'name' => $slug, 'content' => "{$tunnelId}.cfargotunnel.com", 'proxied' => true]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);
echo "  DNS added\n";

// Add Cloudflare Tunnel route
$accountId = '7ea602d1cd797bf3cb34885c9d240114';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$accountId}/cfd_tunnel/{$tunnelId}/configurations");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Email: {$cfEmail}", "X-Auth-Key: {$cfKey}", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
curl_close($ch);
$tunnel = json_decode($resp, true);
$ingress = $tunnel['result']['config']['ingress'] ?? [];

$newIngress = [];
foreach ($ingress as $r) {
    if (isset($r['service']) && $r['service'] === 'http_status:404') continue;
    $newIngress[] = $r;
    if (isset($r['hostname']) && $r['hostname'] === 'tornops.net') {
        $newIngress[] = ['hostname' => "{$slug}.{$domain}", 'service' => "http://192.168.1.75:{$port}"];
    }
}
$newIngress[] = ['service' => 'http_status:404'];

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

echo "[DONE] Faction {$slug} created!\n";
echo "URL: https://{$slug}.{$domain}\n";
echo "PORT: {$port}\n";
