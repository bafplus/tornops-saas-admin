<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class FactionService
{
    protected string $basePath;
    protected string $sourcePath;
    protected string $portStart;

    public function __construct()
    {
        $this->basePath = config('app.tornops_base_path', '/home/server/tornops-instances');
        $this->sourcePath = '/home/server/tornops-code';
        $this->portStart = config('app.tornops_port_start', 8082);
    }

    public function createFactionInstance(string $slug, string $masterKey): bool
    {
        $instancePath = "{$this->basePath}/{$slug}";

        try {
            Process::run("mkdir -p {$instancePath}");
            Process::run("cp -r {$this->sourcePath}/* {$instancePath}/");
            Process::run("mkdir -p {$instancePath}/storage");
            Process::run("mkdir -p {$instancePath}/bootstrap/cache");
            Process::run("chmod -R 777 {$instancePath}/storage {$instancePath}/bootstrap/cache");

            Process::run("cd {$instancePath} && cp .env.example .env 2>/dev/null || true");
            
            $dbPath = "{$instancePath}/storage/database.sqlite";
            Process::run("echo 'DB_CONNECTION=sqlite' > {$instancePath}/.env");
            Process::run("echo 'DB_DATABASE={$dbPath}' >> {$instancePath}/.env");
            Process::run("echo 'MASTER_KEY={$masterKey}' >> {$instancePath}/.env");
            Process::run("echo 'TORN_API_KEY=demo' >> {$instancePath}/.env");

            Process::run("touch {$dbPath} && chmod 666 {$dbPath}");

            Process::run("cd {$instancePath} && php artisan key:generate 2>/dev/null || true");
            Process::run("cd {$instancePath} && php artisan migrate --force 2>/dev/null || true");

            $port = $this->getNextPort();
            Process::run("cd {$instancePath} && nohup php -S 0.0.0.0:{$port} -t public > /tmp/tornops-{$slug}.log 2>&1 &");

            Log::info("Created faction instance", [
                'slug' => $slug,
                'port' => $port,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create faction instance", [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
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
            Process::run("pkill -f 'tornops-instances/{$slug}'");
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
}