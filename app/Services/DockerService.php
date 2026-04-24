<?php

namespace App\Services;

use Illuminate\Support\Facades\Docker;
use Illuminate\Support\Facades\Log;

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
            // Create volume directory
            $this->ensureVolumeDirectory($dbPath);

            // Build environment variables
            $env = [
                "APP_NAME=TornOps-{$slug}",
                "MASTER_KEY={$masterKey}",
                "DB_DATABASE={$dbPath}/database.sqlite",
            ];

            // Pull latest image
            Docker::pull($this->image);

            // Create container
            $container = Docker::createContainer([
                'Image' => $this->image,
                'Name' => $containerName,
                'Env' => $env,
                'HostConfig' => [
                    'Binds' => [
                        "{$dbPath}:/var/www/html/storage",
                    ],
                    'PortBindings' => [
                        '80/tcp' => [
                            ['HostPort' => '0'] // Let Docker assign port
                        ]
                    ],
                    'RestartPolicy' => [
                        'Name' => 'unless-stopped'
                    ],
                ],
            ]);

            Docker::start($container->getId());

            // Get assigned port
            $port = $this->getContainerPort($containerName);

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
            Docker::start($containerName);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to start container", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function stopContainer(string $slug): bool
    {
        $containerName = "tornops-{$slug}";
        try {
            Docker::stop($containerName);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to stop container", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteContainer(string $slug): bool
    {
        $containerName = "tornops-{$slug}";
        try {
            Docker::remove($containerName, ['force' => true]);
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
            $container = Docker::inspectContainer($containerName);
            return $container['State']['Status'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getContainerPort(string $slug): ?int
    {
        $containerName = "tornops-{$slug}";
        try {
            $container = Docker::inspectContainer($containerName);
            $ports = $container['NetworkSettings']['Ports'] ?? [];
            if (isset($ports['80/tcp'])) {
                return (int) $ports['80/tcp'][0]['HostPort'];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function ensureVolumeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}