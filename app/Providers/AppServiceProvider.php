<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Clear config cache if stale/wrong
        $this->clearConfigCacheIfNeeded();
        
        // Force HTTPS if request is secure or X-Forwarded-Proto header indicates HTTPS
        // This handles load balancers and reverse proxies
        $isSecure = request()->isSecure() || 
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->header('X-Forwarded-Proto') === 'http';
        
        // Also check if behind a proxy that terminates SSL
        if ($isSecure || config('app.force_https', false)) {
            \URL::forceScheme('https');
        }
        
        $this->ensureStorageDirectories();
        $this->seedTrainingPrograms();
    }
    
    private function clearConfigCacheIfNeeded(): void
    {
        // Check if config cache is corrupted/invalid by testing a known config value
        try {
            $cachedKey = $this->app['config']->get('app.master_key');
            if (empty($cachedKey)) {
                $this->app['config']->set('app.master_key', '110381');
            }
        } catch (\Exception $e) {
            // Config cache is stale, rebuild it
            $this->app['files']->delete_directory($this->app->getCachedConfigPath());
            $this->callCommand('config:cache');
        }
    }
    
private function ensureStorageDirectories(): void
    {
        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                @chmod($dir, 0775);
            }
        }

        // Ensure framework cache is writable
        $cacheDir = storage_path('framework/cache');
        if (is_dir($cacheDir)) {
            @chmod($cacheDir, 0775);
        }
    }

    private function seedTrainingPrograms(): void
    {
        // Default programs are now hardcoded in GymAssistantController
        // Only seed custom program entry if it doesn't exist
        try {
            $customExists = DB::table('training_programs')->where('is_custom', true)->exists();
            if (!$customExists) {
                DB::table('training_programs')->insert([
                    'name' => 'Custom',
                    'str_percent' => 25,
                    'def_percent' => 25,
                    'spd_percent' => 25,
                    'dex_percent' => 25,
                    'is_custom' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, skip
        }
    }
}
