<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Services\DiscordBotService;
use Illuminate\Console\Command;

class DiscordBotCommand extends Command
{
    protected $signature = 'torn:discord {action : start|stop|restart|status}';
    protected $description = 'Manage the Discord bot';

    public function handle(): int
    {
        $settings = FactionSettings::first();
        
        if (!$settings || !$settings->discord_enabled) {
            $this->error('Discord bot is not enabled. Go to Admin settings to enable it.');
            return Command::FAILURE;
        }

        $discord = app(DiscordBotService::class);
        
        $action = $this->argument('action');
        
        switch ($action) {
            case 'start':
                if ($discord->isRunning()) {
                    $this->info('Discord bot is already running.');
                    return Command::SUCCESS;
                }
                $this->info('Starting Discord bot...');
                $discord->start();
                $this->info('Discord bot started.');
                break;
                
            case 'stop':
                $this->info('Stopping Discord bot...');
                $discord->stop();
                $this->info('Discord bot stopped.');
                break;
                
            case 'restart':
                $this->info('Restarting Discord bot...');
                $discord->restart();
                $this->info('Discord bot restarted.');
                break;
                
            case 'status':
                if ($discord->isRunning()) {
                    $this->info('Discord bot is RUNNING');
                } else {
                    $this->info('Discord bot is NOT running');
                }
                break;
                
            default:
                $this->error('Invalid action. Use: start, stop, restart, or status');
                return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
