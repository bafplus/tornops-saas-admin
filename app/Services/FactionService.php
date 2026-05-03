<?php

namespace App\Services;

use App\Models\Faction;

class FactionService
{
    protected $basePath = '/home/server/tornops-instances';
    protected $image = 'ghcr.io/bafplus/tornops/tornops:latest';
    protected $domain = 'tornops.net';
    protected $cfEmail = 'bart.cockheyt@gmail.com';
    protected $cfKey = 'd888ebd7a3d4da09d50f35b1c6dea49d92f96';
    protected $zoneId = '677fb6d5519e5c230f1de82aba4e8cb2';
    protected $tunnelId = 'b726eb72-ac86-40e7-9b2b-266943fd2110';
    protected $accountId = '7ea602d1cd797bf3cb34885c9d240114';

    public function createFactionInstance(Faction $faction, string $tornApiKey = ''): array
    {
        $slug = $faction->slug;
        $instancePath = "{$this->basePath}/{$slug}";

        // 1. Create directories
        if (!is_dir($instancePath)) {
            mkdir($instancePath, 0755, true);
            mkdir("{$instancePath}/storage", 0755, true);
            mkdir("{$instancePath}/data", 0755, true);
        }

        // 2. Create .env file
        $env = "TORN_API_KEY=" . ($tornApiKey ?: 'demo') . "\n";
        $env .= "MASTER_KEY={$faction->master_key}\n";
        $env .= "APP_ENV=production\n";
        $env .= "APP_DEBUG=false\n";
        file_put_contents("{$instancePath}/.env", $env);
        chmod("{$instancePath}/.env", 0644);

        // 3. Find available port
        $usedPorts = [];
        exec("docker ps --format '{{.Ports}}'", $portsOutput);
        foreach ($portsOutput as $line) {
            if (preg_match('/:(\d+)/', $line, $m)) {
                $usedPorts[] = (int)$m[1];
            }
        }
        foreach (Faction::whereNotNull('port')->pluck('port') as $p) {
            $usedPorts[] = (int)$p;
        }
        $port = 8082;
        while (in_array($port, $usedPorts)) {
            $port++;
        }

        // 4. Create docker-compose.yml
        $compose = "services:\n";
        $compose .= "  tornops:\n";
        $compose .= "    image: {$this->image}\n";
        $compose .= "    restart: unless-stopped\n";
        $compose .= "    container_name: {$slug}\n";
        $compose .= "    ports:\n";
        $compose .= "      - \"{$port}:80\"\n";
        $compose .= "    volumes:\n";
        $compose .= "      - \"./storage:/app/storage\"\n";
        $compose .= "      - \"./data:/data\"\n";
        $compose .= "    env_file:\n";
        $compose .= "      - \".env\"\n";
        file_put_contents("{$instancePath}/docker-compose.yml", $compose);

        // 5. Start container
        $oldDir = getcwd();
        chdir($instancePath);
        exec("docker-compose up -d 2>&1", $output);
        chdir($oldDir);

        // 6. Add Cloudflare DNS
        $this->addCloudflareDns($slug);

        // 7. Update Cloudflare Tunnel ingress config
        $this->updateTunnelIngress($slug, $port);

        return ['port' => $port, 'output' => implode("\n", $output)];
    }

    public function startContainer(string $slug): bool
    {
        $path = "{$this->basePath}/{$slug}";
        if (!is_dir($path)) return false;

        $oldDir = getcwd();
        chdir($path);
        exec("docker-compose up -d 2>&1");
        chdir($oldDir);
        return true;
    }

    public function stopContainer(string $slug): bool
    {
        $path = "{$this->basePath}/{$slug}";
        if (!is_dir($path)) return false;

        $oldDir = getcwd();
        chdir($path);
        exec("docker-compose down 2>&1");
        chdir($oldDir);
        return true;
    }

    public function updateInstance(Faction $faction, string $oldSlug): bool
    {
        $newSlug = $faction->slug;
        $oldPath = "{$this->basePath}/{$oldSlug}";
        $newPath = "{$this->basePath}/{$newSlug}";

        // 1. Stop container with old slug
        $this->stopContainer($oldSlug);

        // 2. Rename folder
        if (is_dir($oldPath)) {
            exec("mv {$oldPath} {$newPath}");
        }

        // 3. Delete old DNS + tunnel ingress
        $this->deleteCloudflareDns($oldSlug);
        $this->removeTunnelIngress($oldSlug);

        // 4. Update Docker container name in .env and compose files
        $envPath = "{$newPath}/.env";
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = preg_replace('/^MASTER_KEY=.*/m', "MASTER_KEY={$faction->master_key}", $env);
            file_put_contents($envPath, $env);
        }

        $composePath = "{$newPath}/docker-compose.yml";
        if (file_exists($composePath)) {
            $compose = file_get_contents($composePath);
            $compose = str_replace("container_name: {$oldSlug}", "container_name: {$newSlug}", $compose);
            file_put_contents($composePath, $compose);
        }

        // 5. Save faction (updates slug in DB)
        $faction->save();

        // 6. Create new DNS + tunnel ingress
        $this->addCloudflareDns($newSlug);
        $this->updateTunnelIngress($newSlug, $faction->port);

        // 7. Start container with new slug
        $this->startContainer($newSlug);

        return true;
    }

    public function deleteContainer(string $slug): bool
    {
        $this->stopContainer($slug);
        $this->deleteCloudflareDns($slug);
        $this->removeTunnelIngress($slug);
        $path = "{$this->basePath}/{$slug}";
        if (is_dir($path)) {
            exec("rm -rf {$path}");
        }
        return true;
    }

    public function getContainerStatus(string $slug): string
    {
        $output = [];
        exec("docker ps -q -f name={$slug}", $output);
        return !empty($output) ? 'running' : 'stopped';
    }

    public function loginAs(Faction $faction): string
    {
        $url = "https://{$faction->slug}.{$this->domain}/master-login?master_key={$faction->master_key}";
        return $url;
    }

    public function regenerateKey(Faction $faction): string
    {
        $newKey = bin2hex(random_bytes(16));
        $faction->update([
            'master_key' => $newKey,
            'master_key_generated_at' => now(),
        ]);

        $envPath = "{$this->basePath}/{$faction->slug}/.env";
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = preg_replace('/MASTER_KEY=.*/', "MASTER_KEY={$newKey}", $env);
            file_put_contents($envPath, $env);
        }

        return $newKey;
    }

    private function addCloudflareDns(string $slug): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'type' => 'CNAME',
            'name' => $slug,
            'content' => "{$this->tunnelId}.cfargotunnel.com",
            'proxied' => true
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    private function deleteCloudflareDns(string $slug): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records?type=CNAME&name={$slug}.{$this->domain}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $records = json_decode($response, true);
        if (isset($records['result'][0]['id'])) {
            $recordId = $records['result'][0]['id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$recordId}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Auth-Email: {$this->cfEmail}",
                "X-Auth-Key: {$this->cfKey}",
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    private function updateTunnelIngress(string $slug, int $port): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}/configurations");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $currentConfig = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $ingress = [];
        if (isset($currentConfig['result']['config']['ingress'])) {
            $ingress = $currentConfig['result']['config']['ingress'];
        }
        $catchAll = array_pop($ingress);
        $ingress[] = [
            'hostname' => "{$slug}.{$this->domain}",
            'service' => "http://192.168.1.75:{$port}"
        ];
        $ingress[] = $catchAll;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}/configurations");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'config' => ['ingress' => $ingress]
        ]));
        curl_exec($ch);
        curl_close($ch);
    }

    private function removeTunnelIngress(string $slug): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}/configurations");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $currentConfig = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $ingress = [];
        if (isset($currentConfig['result']['config']['ingress'])) {
            $ingress = $currentConfig['result']['config']['ingress'];
        }
        $catchAll = array_pop($ingress);
        $ingress = array_filter($ingress, function ($rule) use ($slug) {
            return !isset($rule['hostname']) || $rule['hostname'] !== "{$slug}.{$this->domain}";
        });
        $ingress[] = $catchAll;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/cfd_tunnel/{$this->tunnelId}/configurations");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Email: {$this->cfEmail}",
            "X-Auth-Key: {$this->cfKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'config' => ['ingress' => $ingress]
        ]));
        curl_exec($ch);
        curl_close($ch);
    }
}
