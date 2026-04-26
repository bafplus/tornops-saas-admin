<?php

namespace App\Jobs;

use App\Models\Faction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class CreateFactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Faction $faction;
    public string $masterKey;

    public function __construct(Faction $faction, string $masterKey)
    {
        $this->faction = $faction;
        $this->masterKey = $masterKey;
    }

    public function handle(): void
    {
        $slug = $this->faction->slug;
        $basePath = '/home/server/tornops-instances';
        $instancePath = "{$basePath}/{$slug}";
        
        try {
            $this->updateLog("Creating directories...");
            
            // 1. Create directories
            Process::run("mkdir -p {$instancePath}");
            Process::run("mkdir -p {$instancePath}/storage");
            Process::run("mkdir -p {$instancePath}/data");
            Process::run("mkdir -p {$instancePath}/bootstrap/cache");
            Process::run("chmod -R 777 {$instancePath}/storage {$instancePath}/data {$instancePath}/bootstrap/cache");

            $this->updateLog("Cloning TornOps repository...");

            // 2. Clone code from git
            Process::run("cd {$instancePath} && git clone https://github.com/bafplus/tornops.git .");

            $this->updateLog("Setting up environment...");

            // 3. Set up .env
            $envContent = implode("\n", [
                "APP_NAME=TornOps-{$slug}",
                "APP_ENV=production", 
                "MASTER_KEY={$this->masterKey}",
                "TORN_API_KEY=" . ($this->faction->torn_api_key ?? 'demo'),
                "DB_CONNECTION=sqlite",
                "DB_DATABASE={$instancePath}/data/database.sqlite",
                "SESSION_DRIVER=file",
                "CACHE_STORE=file",
            ]);
            file_put_contents("{$instancePath}/.env", $envContent);
            chmod("{$instancePath}/.env", 600);

            // 4. Fix /data path in setup view
            $setupView = "{$instancePath}/resources/views/setup/index.blade.php";
            if (file_exists($setupView)) {
                $content = file_get_contents($setupView);
                $content = str_replace('/data', $instancePath . '/data', $content);
                file_put_contents($setupView, $content);
            }

            // 5. Create symlink
            if (!is_link("{$instancePath}/data/storage")) {
                symlink("{$instancePath}/storage", "{$instancePath}/data/storage");
            }

            $this->updateLog("Installing dependencies...");

            // 6. Install composer dependencies
            Process::run("cd {$instancePath} && composer install --no-interaction --prefer-dist --no-dev 2>&1");

            $this->updateLog("Setting up database...");

            // 7. Setup database
            $dbPath = "{$instancePath}/data/database.sqlite";
            Process::run("touch {$dbPath} && chmod 666 {$dbPath}");
            
            Process::run("cd {$instancePath} && php artisan key:generate 2>&1");
            Process::run("cd {$instancePath} && php artisan config:clear 2>&1");
            Process::run("cd {$instancePath} && php artisan migrate --force 2>&1");
            Process::run("cd {$instancePath} && php artisan jobs:seed 2>&1");

            // 8. Find available port
            $port = $this->findPort();

            $this->updateLog("Starting server on port {$port}...");

            // 9. Start PHP server
            Process::run("pkill -f 'tornops-instances/{$slug}' 2>/dev/null || true");
            Process::run("cd {$instancePath} && nohup php -S 0.0.0.0:{$port} -t public > /tmp/tornops-{$slug}.log 2>&1 &");

            // Wait for server to start
            sleep(2);

            // 10. Update faction with status
            $this->faction->update([
                'status' => 'active',
                'port' => $port,
                'log' => "Done! Site available at https://{$slug}.tornops.net",
            ]);

            Log::info("Faction created successfully", ['slug' => $slug, 'port' => $port]);

        } catch (\Exception $e) {
            $this->updateLog("Error: " . $e->getMessage());
            $this->faction->update([
                'status' => 'error',
                'log' => "Error: " . $e->getMessage(),
            ]);
            Log::error("Faction creation failed", ['slug' => $slug, 'error' => $e->getMessage()]);
        }
    }

    protected function updateLog(string $message): void
    {
        $current = $this->faction->log ?? '';
        $this->faction->update([
            'log' => $current . date('H:i:s') . ' ' . $message . "\n",
        ]);
    }

    protected function findPort(): int
    {
        $result = Process::run("ss -tlnp | grep php");
        $usedPorts = [];
        if ($result->successful()) {
            preg_match_all('/:(\d+)/', $result->output(), $matches);
            $usedPorts = array_map('intval', $matches[1] ?? []);
        }
        
        for ($port = 8082; $port < 8200; $port++) {
            if (!in_array($port, $usedPorts)) {
                return $port;
            }
        }
        return 8082;
    }
}