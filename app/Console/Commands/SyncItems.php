<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use Illuminate\Console\Command;

class SyncItems extends Command
{
    protected $signature = 'torn:sync-items';
    protected $description = 'Sync items from Torn API to database';

    public function handle(TornApiService $tornApi): int
    {
        $log = DataRefreshLog::logStart('items');
        
        $this->info("Syncing items from Torn API...");
        
        $data = $tornApi->getItems();

        if (!$data || !isset($data['items'])) {
            $this->error('Failed to fetch items.');
            $log->fail('API error');
            return Command::FAILURE;
        }

        $items = $data['items'];
        $synced = 0;

        foreach ($items as $itemData) {
            $value = $itemData['value'] ?? [];
            
            Item::updateOrCreate(
                ['id' => $itemData['id']],
                [
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'type' => $itemData['type'] ?? null,
                    'sub_type' => $itemData['sub_type'] ?? null,
                    'is_tradable' => $itemData['is_tradable'] ?? true,
                    'is_found_in_city' => $itemData['is_found_in_city'] ?? false,
                    'buy_price' => $value['buy_price'] ?? null,
                    'sell_price' => $value['sell_price'] ?? null,
                    'market_price' => $value['market_price'] ?? null,
                    'circulation' => $itemData['circulation'] ?? null,
                    'image' => $itemData['image'] ?? null,
                    'last_synced_at' => now(),
                ]
            );
            $synced++;
        }

        $this->info("Synced {$synced} items.");
        $log->markComplete();
        return Command::SUCCESS;
    }
}
