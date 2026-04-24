<?php

namespace App\Providers;

use App\Models\FactionSettings;
use App\Services\DiscordBotService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class DiscordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DiscordBotService::class, function ($app) {
            return new DiscordBotService();
        });
    }

    public function boot(): void
    {
        try {
            $settings = FactionSettings::first();
            
            if ($settings && $settings->discord_enabled && $settings->discord_bot_token) {
                $discord = app(DiscordBotService::class);
                
                if (!$discord->isRunning()) {
                    Log::info('Auto-starting Discord bot...');
                    $discord->start();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to auto-start Discord bot: ' . $e->getMessage());
        }
    }
}
