<?php

namespace App\Console\Commands;

use App\Services\TornApiService;
use App\Models\FactionSettings;
use App\Models\StockHistory;
use App\Models\DataRefreshLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncStocks extends Command
{
    protected $signature = 'torn:sync-stocks {--force : Force sync even during active war}';
    protected $description = 'Sync stock prices and save to history';

    public function handle()
    {
        $log = DataRefreshLog::logStart('stocks');
        
        $settings = FactionSettings::first();
        $apiKey = $settings?->torn_api_key;
        
        if (!$apiKey) {
            $this->error('No faction API key found.');
            $log->fail('No API key');
            return 1;
        }

        Cache::forget('stocks_data');
        
        $tornApi = app(TornApiService::class);
        $rawStocks = $tornApi->getStocks($apiKey);
        
        if (!$rawStocks) {
            $this->error('Failed to fetch stock data.');
            $log->fail('API error');
            return 1;
        }

        $now = now();
        $count = 0;
        
        // Cleanup old records (keep max 24 hours)
        StockHistory::where('created_at', '<', $now->subHours(24))->delete();
        
        foreach ($rawStocks as $stock) {
            StockHistory::updateOrCreate(
                ['stock_id' => $stock['id'] ?? 0],
                [
                    'name' => $stock['name'] ?? '',
                    'acronym' => $stock['acronym'] ?? '',
                    'price' => $stock['market']['price'] ?? 0,
                    'investors' => $stock['market']['investors'] ?? 0,
                    'shares' => $stock['market']['shares'] ?? 0,
                    'market_cap' => $stock['market']['cap'] ?? 0,
                    'recorded_at' => $now,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} stocks and saved to history.");
        $log->markComplete();
        return 0;
    }
}
