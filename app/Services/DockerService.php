<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DockerService
{
    protected string $image;
    protected string $volumePath;

    public function __construct()
    {
        $this->image = config('app.tornops_image', 'ghcr.io/bafplus/tornops/tornops:latest');
        $this->volumePath = config('app.data_volume_path', '/data/tornops');
    }

    public function createFactionContainer(string $slug, string $masterKey): bool
    {
        $containerName = "tornops-{$slug}";
        $dbPath = "{$this->volumePath}/{$slug}";

        try {
            $this->ensureVolumeDirectory($dbPath);

            $result = Process::run("docker run -d \
                --name {$containerName} \
                -e APP_NAME=TornOps-{$slug} \
                -e MASTER_KEY={$masterKey} \
                -e DB_CONNECTION=sqlite \
                -e DB_DATABASE=/var/www/html/storage/database.sqlite \
                -v {$dbPath}:/var/www/html/storage \
                -e TORN_API_KEY=demo \
                --restart unless-stopped \
                {$this->image}");

            if ($result->failed()) {
                Log::error("Docker run failed", [
                    'slug' => $slug,
                    'output' => $result->output(),
                    'error' => $result->error(),
                ]);
                return false;
            }

            $port = $this->getContainerPort($slug);

            Log::info("Created faction container", [
                'slug' => $slug,
                'container' => $containerName,
                'port' => $port,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create container", [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function startContainer(string $slug): bool
    {
        $containerName = "tornops-{$slug}";
        try {
            $result = Process::run("docker start {$containerName}");
            return $result->successful();
        } catch (\Exception $e) {
            Log::error("Failed to start container", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function stopContainer(string $slug): bool
    {
        $containerName = "tornops-{$slug}";
        try {
            $result = Process::run("docker stop {$containerName}");
            return $result->successful();
        } catch (\Exception $e) {
            Log::error("Failed to stop container", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteContainer(string $slug): bool
    {
        $containerName = "tornops-{$slug}";
        try {
            Process::run("docker rm -f {$containerName}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete container", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getContainerStatus(string $slug): ?string
    {
        $containerName = "tornops-{$slug}";
        try {
            $result = Process::run("docker inspect -f '{{.State.Status}}' {$containerName}");
            if ($result->successful()) {
                return trim($result->output());
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getContainerPort(string $slug): ?int
    {
        $containerName = "tornops-{$slug}";
        try {
            $result = Process::run("docker port {$containerName} 80");
            if ($result->successful()) {
                $output = trim($result->output());
                if (preg_match('/:(\d+)$/', $output, $matches)) {
                    return (int) $matches[1];
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function listContainers(): array
    {
        $containers = [];
        try {
            $result = Process::run("docker ps -a --filter 'name=tornops-' --format '{{.Names}}\t{{.Status}}\t{{.Ports}}'");
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                foreach ($lines as $line) {
                    if ($line) {
                        $parts = explode("\t", $line);
                        $containers[] = [
                            'name' => $parts[0] ?? '',
                            'status' => $parts[1] ?? '',
                            'ports' => $parts[2] ?? '',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // ignore
        }
        return $containers;
    }

    protected function ensureVolumeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}