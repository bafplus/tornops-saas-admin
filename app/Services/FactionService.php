<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;

class FactionService
{
    protected string $basePath;
    protected string $sourcePath;
    protected string $portStart;
    protected string $cloudflareEmail;
    protected string $cloudflareKey;
    protected string $cloudflareAccountId;
    protected string $cloudflareTunnelId;
    protected string $cloudflareZoneId;
    protected string $domain;

    public function __construct()
    {
        $this->basePath = config('app.tornops_base_path', '/home/server/tornops-instances');
        $this->sourcePath = '/home/bart/tornops-code';
        $this->portStart = config('app.tornops_port_start', 8082);
        $this->domain = config('app.tornops_domain', 'tornops.net');
        
        // Cloudflare config
        $this->cloudflareEmail = config('app.cloudflare_email', 'bart.cockheyt@gmail.com');
        $this->cloudflareKey = config('app.cloudflare_key', 'd888ebd7a3d4da09d50f35b1c6dea49d92f96');
        $this->cloudflareAccountId = config('app.cloudflare_account_id', '7ea602d1cd797bf3cb34885c9d240114');
        $this->cloudflareTunnelId = config('app.cloudflare_tunnel_id', 'b5999d7d-67f0-461f-9513-7b8dcb15766d');
        $this->cloudflareZoneId = config('app.cloudflare_zone_id', '677fb6d5519e5c230f1de82aba4e8cb2');
    }

    public function createFactionInstance(string $slug, string $masterKey, ?string $tornApiKey = null): array
    {
        $instancePath = "{$this->basePath}/{$slug}";
        $port = $this->getNextPort();

        try {
            // 1. Copy source code
            $this->copySourceCode($instancePath);
            
            // 2. Setup directories and permissions
            $this->setupDirectories($instancePath);
            
            // 3. Create .env config
            $dbPath = "{$instancePath}/storage/database.sqlite";
            $this->createEnv($instancePath, $dbPath, $masterKey, $tornApiKey);
            
            // 4. Fix /data path in setup view
            $this->fixDataPath($instancePath);
            
            // 5. Install dependencies and setup database
            $this->setupDatabase($instancePath, $dbPath);
            
            // 6. Start the PHP server
            $this->startServer($instancePath, $port, $slug);
            
            // 7. Add Cloudflare DNS and tunnel route
            $this->addCloudflareRoute($slug, $port);

            Log::info("Created faction instance", [
                'slug' => $slug,
                'port' => $port,
            ]);

            return [
                'success' => true,
                'port' => $port,
                'url' => "https://{$slug}.{$this->domain}",
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create faction instance", [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function copySourceCode(string $instancePath): void
    {
        Process::run("mkdir -p {$instancePath}");
        Process::run("cd {$instancePath} && git clone https://github.com/bafplus/tornops.git .");
    }

    protected function setupDirectories(string $instancePath): void
    {
        Process::run("mkdir -p {$instancePath}/storage");
        Process::run("mkdir -p {$instancePath}/bootstrap/cache");
        Process::run("mkdir -p {$instancePath}/data");
        Process::run("chmod -R 777 {$instancePath}/storage {$instancePath}/bootstrap/cache {$instancePath}/data");
        Process::run("ln -sf {$instancePath}/storage {$instancePath}/data/storage");
    }

    protected function createEnv(string $instancePath, string $dbPath, string $masterKey, ?string $tornApiKey): void
    {
        $apiKey = $tornApiKey ?: 'demo';
        $appKey = 'base64:' . bin2hex(random_bytes(16));
        $envContent = <<<ENV
APP_NAME=TornOps-{$instancePath}
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE={$dbPath}
MASTER_KEY={$masterKey}
TORN_API_KEY={$apiKey}
SESSION_DRIVER=file
CACHE_STORE=file
APP_KEY={$appKey}
ENV;
        
        file_put_contents("{$instancePath}/.env", $envContent);
        chmod("{$instancePath}/.env", 644);
    }

    protected function fixDataPath(string $instancePath): void
    {
        $viewPath = "{$instancePath}/resources/views/setup/index.blade.php";
        if (file_exists($viewPath)) {
            $content = file_get_contents($viewPath);
            $content = str_replace('/data', "{$instancePath}/data", $content);
            file_put_contents($viewPath, $content);
        }
    }

protected function setupDatabase(string $instancePath, string $dbPath): void
    {
        Log::info("Starting setupDatabase", ['path' => $instancePath]);
        Process::run("touch {$dbPath} && chmod 666 {$dbPath}");
        Log::info("Touched database", ['path' => $dbPath]);
        
        $compResult = Process::run("cd {$instancePath} && composer install --no-interaction 2>&1");
        Log::info("Composer install", ['output' => $compResult->output(), 'success' => $compResult->successful()]);
        
        // Generate a new APP_KEY and write directly to .env (bypass permission issues)
        $appKey = 'base64:' . bin2hex(random_bytes(16));
        $envFile = "{$instancePath}/.env";
        $envContent = file_get_contents($envFile);
        // Replace APP_KEY line (exact match)
        $lines = explode("\n", $envContent);
        foreach ($lines as $i => $line) {
            if (str_starts_with(trim($line), 'APP_KEY=')) {
                $lines[$i] = 'APP_KEY=' . $appKey;
            }
        }
        $envContent = implode("\n", $lines);
        file_put_contents($envFile, $envContent);
        chmod($envFile, 644);
        
        // Clear any cached config
        Process::run("rm -f {$instancePath}/bootstrap/cache/*.php 2>/dev/null");
        Process::run("cd {$instancePath} && php artisan config:clear 2>&1");
        
        $migResult = Process::run("cd {$instancePath} && php artisan migrate --force 2>&1");
        Log::info("Migrate", ['output' => $migResult->output(), 'success' => $migResult->successful()]);
        
        $jobsResult = Process::run("cd {$instancePath} && php artisan jobs:seed 2>&1");
        Log::info("Jobs seed", ['output' => $jobsResult->output(), 'success' => $jobsResult->successful()]);
    }
    
    protected function startServer(string $instancePath, int $port, string $slug): void
    {
        Log::info("Starting server", ['slug' => $slug, 'port' => $port, 'path' => $instancePath]);
        
        // Wait a moment to ensure .env is ready
        usleep(500000); // 0.5 second
        
        $cmd = "cd {$instancePath} && nohup php -S 0.0.0.0:{$port} -t public > /tmp/tornops-{$slug}.log 2>&1 &";
        Log::info("Running start command", ['cmd' => $cmd]);
        
        exec($cmd);
        
        // Wait for server to be ready
        usleep(500000); // 0.5 second
        
        // Verify APP_KEY exists, fix if needed
        $keyCheck = "cd {$instancePath} && php -r \"echo config('app.key');\" 2>&1";
        $keyResult = [];
        exec($keyCheck, $keyResult);
        if (empty(trim(implode('', $keyResult)))) {
            Log::info("APP_KEY still missing, fixing after start");
            $appKey = base64_encode(random_bytes(16));
            $envFile = "{$instancePath}/.env";
            $envContent = file_get_contents($envFile);
            $envContent = preg_replace('/APP_KEY=.*/', "APP_KEY=base64:{$appKey}", $envContent);
            file_put_contents($envFile, $envContent);
            chmod($envFile, 644);
            exec("cd {$instancePath} && php artisan config:clear >/dev/null 2>&1");
        }
        
        Log::info("Server started", ['slug' => $slug]);
    }

    public function addCloudflareRoute(string $slug, int $port): void
    {
        $hostname = "{$slug}.{$this->domain}";
        
        // Add DNS CNAME
        $this->cfRequest('POST', "/zones/{$this->cloudflareZoneId}/dns_records", [
            'type' => 'CNAME',
            'name' => $slug,
            'content' => "{$this->cloudflareTunnelId}.cfargotunnel.com",
            'proxied' => true,
        ]);

        // Get current tunnel config
        $config = $this->cfRequest('GET', "/accounts/{$this->cloudflareAccountId}/cfd_tunnel/{$this->cloudflareTunnelId}/configurations");
        
        // Add new ingress rule (before tornops.net to take priority)
        $ingress = $config['result']['config']['ingress'] ?? [];
        
        // Find position of tornops.net and insert before it
        $newRule = [
            'hostname' => $hostname,
            'service' => "http://217.154.154.7:{$port}",
            'originRequest' => (object)[],
        ];
        
        $newIngress = [];
        foreach ($ingress as $rule) {
            if (($rule['hostname'] ?? '') === 'tornops.net') {
                $newIngress[] = $newRule;
            }
            $newIngress[] = $rule;
        }
        
        // Save new config
        try {
            $this->cfRequest('PUT', "/accounts/{$this->cloudflareAccountId}/cfd_tunnel/{$this->cloudflareTunnelId}/configurations", [
                'config' => [
                    'ingress' => $newIngress,
                    'warp-routing' => ['enabled' => false],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Tunnel route failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function removeCloudflareRoute(string $slug): void
    {
        $hostname = "{$slug}.{$this->domain}";
        
        // Delete DNS record - use full hostname
        $dnsRecords = $this->cfRequest('GET', "/zones/{$this->cloudflareZoneId}/dns_records?name={$hostname}");
        foreach ($dnsRecords['result'] ?? [] as $record) {
            if ($record['name'] === $hostname) {
                $this->cfRequest('DELETE', "/zones/{$this->cloudflareZoneId}/dns_records/{$record['id']}");
            }
        }

        // Remove from tunnel config
        $config = $this->cfRequest('GET', "/accounts/{$this->cloudflareAccountId}/cfd_tunnel/{$this->cloudflareTunnelId}/configurations");
        
        $ingress = array_filter($config['result']['config']['ingress'] ?? [], function($rule) use ($hostname) {
            return ($rule['hostname'] ?? '') !== $hostname;
        });
        
        $this->cfRequest('PUT', "/accounts/{$this->cloudflareAccountId}/cfd_tunnel/{$this->cloudflareTunnelId}/configurations", [
            'config' => [
                'ingress' => array_values($ingress),
                'warp-routing' => ['enabled' => false],
            ],
        ]);
    }

    protected function cfRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $url = "https://api.cloudflare.com/client/v4{$endpoint}";
        $headers = [
            'X-Auth-Email: ' . $this->cloudflareEmail,
            'X-Auth-Key: ' . $this->cloudflareKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true) ?: [];
        
        // Check for API errors
        if (!$result['success'] ?? true) {
            $error = $result['errors'][0]['message'] ?? 'Unknown API error';
            Log::error("Cloudflare API error", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $error,
                'http_code' => $httpCode,
            ]);
            throw new \Exception("Cloudflare API error: {$error}");
        }
        
        return $result;
    }

    public function startInstance(string $slug): bool
    {
        $port = $this->getPortForSlug($slug);
        if (!$port) return false;

        try {
            Process::run("cd {$this->basePath}/{$slug} && nohup php -S 0.0.0.0:{$port} -t public > /tmp/tornops-{$slug}.log 2>&1 &");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to start instance", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function stopInstance(string $slug): bool
    {
        try {
            Process::run("pkill -f 'tornops-instances/{$slug}'");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to stop instance", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteInstance(string $slug): bool
    {
        try {
            $this->stopInstance($slug);
            $this->removeCloudflareRoute($slug);
            Process::run("rm -rf {$this->basePath}/{$slug}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete instance", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getInstanceStatus(string $slug): ?string
    {
        try {
            $result = Process::run("ps aux | grep 'tornops-instances/{$slug}' | grep -v grep");
            if ($result->successful() && trim($result->output())) {
                return 'running';
            }
            return 'stopped';
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getInstancePort(string $slug): ?int
    {
        return $this->getPortForSlug($slug);
    }

    public function listInstances(): array
    {
        $instances = [];
        try {
            if (!is_dir($this->basePath)) return [];
            $dirs = glob("{$this->basePath}/*", GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $slug = basename($dir);
                $instances[] = [
                    'slug' => $slug,
                    'status' => $this->getInstanceStatus($slug),
                    'port' => $this->getInstancePort($slug),
                ];
            }
        } catch (\Exception $e) {}
        return $instances;
    }

    protected function getNextPort(): int
    {
        $usedPorts = [];
        try {
            $result = Process::run("ss -tlnp | grep php");
            if ($result->successful()) {
                preg_match_all('/:(\d+)/', $result->output(), $matches);
                $usedPorts = array_map('intval', $matches[1] ?? []);
            }
        } catch (\Exception $e) {}

        for ($port = $this->portStart; $port < 8200; $port++) {
            if (!in_array($port, $usedPorts)) {
                return $port;
            }
        }
        return $this->portStart;
    }

    protected function getPortForSlug(string $slug): ?int
    {
        try {
            $result = Process::run("ss -tlnp | grep tornops-instances");
            if ($result->successful()) {
                preg_match('/:' . $slug . '.*:(\d+)/', $result->output(), $matches);
                if (isset($matches[1])) {
                    return (int) $matches[1];
                }
            }
        } catch (\Exception $e) {}
        return $this->getNextPort();
    }

    // Aliases for backward compatibility
    public function getContainerStatus(string $slug): ?string
    {
        return $this->getInstanceStatus($slug);
    }

    public function startContainer(string $slug): bool
    {
        return $this->startInstance($slug);
    }

    public function stopContainer(string $slug): bool
    {
        return $this->stopInstance($slug);
    }

    public function deleteContainer(string $slug): bool
    {
        return $this->deleteInstance($slug);
    }
}