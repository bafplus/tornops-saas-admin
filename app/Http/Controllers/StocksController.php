<?php

namespace App\Http\Controllers;

use App\Services\TornApiService;
use App\Services\WarService;
use App\Models\FactionSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class StocksController extends Controller
{
    public function index(TornApiService $tornApi)
    {
        try {
            $warActive = WarService::hasActiveWar();
        
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            return view('stocks.index', [
                'error' => 'No API key found. Please add your Torn API key in Settings.',
                'stocks' => [],
                'history' => collect(),
                'userStocks' => [],
                'recommendations' => collect(),
                'warActive' => $warActive,
            ]);
        }
        
        $rawStocks = Cache::remember('stocks_data', 1800, function () use ($tornApi, $apiKey) {
            return $tornApi->getStocks($apiKey);
        });

        // Save to history hourly
        if ($rawStocks) {
            $now = now();
            $hourKey = $now->format('Y-m-d H');
            
            // Cleanup old records (keep max 24 hours)
            \App\Models\StockHistory::where('created_at', '<', $now->subHours(24))->delete();
            
            // Check if we already synced this hour
            $existsThisHour = \App\Models\StockHistory::where('created_at', '>=', $now->startOfHour())->exists();
            
            if (!$existsThisHour) {
                foreach ($rawStocks as $stock) {
                    \App\Models\StockHistory::create([
                        'stock_id' => $stock['id'] ?? 0,
                        'name' => $stock['name'] ?? '',
                        'acronym' => $stock['acronym'] ?? '',
                        'price' => $stock['market']['price'] ?? 0,
                        'investors' => $stock['market']['investors'] ?? 0,
                        'shares' => $stock['market']['shares'] ?? 0,
                        'market_cap' => $stock['market']['cap'] ?? 0,
                        'recorded_at' => $now,
                    ]);
                }
            }
        }

        if (!$rawStocks) {
            return view('stocks.index', [
                'error' => 'Failed to fetch stock data. Check your API key has stocks access.',
                'stocks' => [],
                'history' => collect(),
                'userStocks' => [],
                'recommendations' => collect(),
                'warActive' => $warActive,
            ]);
        }

        $stocks = collect($rawStocks)->map(function ($stock) {
            $market = $stock['market'] ?? [];
            $price = $market['price'] ?? 0;
            $bonus = $stock['bonus'] ?? null;
            
            $bonusText = '';
            if ($bonus) {
                $freq = match($bonus['frequency'] ?? 0) {
                    1 => 'daily',
                    7 => 'weekly',
                    14 => 'bi-weekly',
                    31 => 'monthly',
                    91 => 'quarterly',
                    default => 'every ' . ($bonus['frequency'] ?? '?') . ' days'
                };
                $bonusText = [
                    'type' => $bonus['passive'] ? 'Passive' : 'Active',
                    'shares_needed' => number_format($bonus['requirement'] ?? 0),
                    'payout' => $bonus['description'] ?? '',
                    'frequency' => $freq,
                ];
            }
            
            $stockId = $stock['id'] ?? 0;
            
            // Get price changes from history
            $dayAgo = \App\Models\StockHistory::where('stock_id', $stockId)
                ->whereDate('recorded_at', now()->subDay())
                ->value('price');
            $day2Ago = \App\Models\StockHistory::where('stock_id', $stockId)
                ->whereDate('recorded_at', now()->subHours(24))
                ->value('price');
            
            return [
                'id' => $stockId,
                'acronym' => $stock['acronym'] ?? '',
                'name' => $stock['name'] ?? '',
                'price' => $price,
                'shares' => $market['shares'] ?? 0,
                'price_24h_ago' => $dayAgo,
                'change_24h' => $dayAgo > 0 ? (($price - $dayAgo) / $dayAgo * 100) : null,
                'price_48h_ago' => $day2Ago,
                'change_48h' => $day2Ago > 0 ? (($price - $day2Ago) / $day2Ago * 100) : null,
                'bonus_passive' => $bonus['passive'] ?? false,
                'bonus_requirement' => $bonus['requirement'] ?? 0,
                'bonus_payout' => $bonus['description'] ?? '',
                'bonus_frequency' => $bonus['frequency'] ?? 0,
                'bonus_text' => $bonusText,
                'logo' => $stock['images']['logo'] ?? null,
            ];
        })->sortByDesc('shares')->values();

        // Generate investment recommendations for passive income
        $recommendations = $stocks->filter(function ($stock) {
            return ($stock['bonus_passive'] ?? false) 
                && ($stock['bonus_requirement'] ?? 0) > 0 
                && !empty($stock['bonus_payout'])
                && $stock['price'] > 0;
        })->map(function ($stock) {
            $costToUnlock = $stock['price'] * $stock['bonus_requirement'];
            $payoutStr = $stock['bonus_payout'];
            
            // Parse payout amount - handle formats like "$50,000,000", "50m", "$100"
            $cleaned = preg_replace('/[^0-9.]/', '', $payoutStr);
            $payoutAmount = (float) $cleaned;
            
            // Handle "m" suffix for millions  
            if (stripos(strtolower($payoutStr), 'm') !== false && is_numeric($cleaned)) {
                $payoutAmount = (float) $cleaned * 1000000;
            }
            
            // Calculate ROI: payout value / investment cost
            $roi = $costToUnlock > 0 ? ($payoutAmount / $costToUnlock * 100) : 0;
            
            return [
                'id' => $stock['id'],
                'name' => $stock['name'],
                'acronym' => $stock['acronym'],
                'price' => $stock['price'],
                'shares_needed' => $stock['bonus_requirement'],
                'cost_to_unlock' => $costToUnlock,
                'payout' => $payoutStr,
                'payout_amount' => $payoutAmount,
                'roi_percent' => $roi,
            ];
        })->sortByDesc('roi_percent')->values();

        // Get history for chart (24 hours)
        $history = \App\Models\StockHistory::selectRaw('stock_id, acronym, name, recorded_at, price')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->get()
            ->groupBy('stock_id');

        // Get user's portfolio - always fetch fresh on page load
        $userStocks = [];
        $userApiKey = $this->getApiKey();
        $userId = Auth::id();
        if ($userApiKey && $userId) {
            $rawUserStocks = $tornApi->getUserStocks($userApiKey);
            
            if ($rawUserStocks) {
                $stockPrices = collect($stocks)->keyBy('id');
                
                foreach ($rawUserStocks as $userStock) {
                    $stockId = $userStock['id'];
                    $shares = $userStock['shares'];
                    $avgPrice = 0;
                    $totalCost = 0;
                    
                    if (isset($userStock['transactions']) && count($userStock['transactions']) > 0) {
                        $totalShares = 0;
                        $totalCost = 0;
                        foreach ($userStock['transactions'] as $tx) {
                            $totalShares += $tx['shares'];
                            $totalCost += $tx['shares'] * $tx['price'];
                        }
                        $avgPrice = $totalShares > 0 ? $totalCost / $totalShares : 0;
                    }
                    
                    $currentPrice = $stockPrices[$stockId]['price'] ?? 0;
                    $value = $shares * $currentPrice;
                    $costBasis = $shares * $avgPrice;
                    $profitLoss = $value - $costBasis;
                    $profitLossPct = $costBasis > 0 ? ($profitLoss / $costBasis * 100) : 0;
                    
                    $userStocks[] = [
                        'id' => $stockId,
                        'name' => $stockPrices[$stockId]['name'] ?? 'Unknown',
                        'acronym' => $stockPrices[$stockId]['acronym'] ?? '',
                        'shares' => $shares,
                        'avg_price' => $avgPrice,
                        'current_price' => $currentPrice,
                        'value' => $value,
                        'profit_loss' => $profitLoss,
                        'profit_loss_pct' => $profitLossPct,
                        'bonus' => $userStock['bonus'] ?? null,
                    ];
                }
                
                // Store in history
                $today = now()->toDateString();
                $userId = Auth::id();
                foreach ($userStocks as $us) {
                    \App\Models\UserStockHolding::updateOrCreate(
                        ['user_id' => $userId, 'stock_id' => $us['id'], 'recorded_at' => $today],
                        [
                            'name' => $us['name'],
                            'acronym' => $us['acronym'],
                            'shares' => $us['shares'],
                            'avg_price' => $us['avg_price'],
                            'current_price' => $us['current_price'],
                            'value' => $us['value'],
                            'profit_loss' => $us['profit_loss'],
                            'profit_loss_pct' => $us['profit_loss_pct'],
                            'bonus' => $us['bonus'],
                        ]
                    );
                }
            }
        }

return view('stocks.index', [
            'stocks' => $stocks,
            'history' => $history,
            'userStocks' => $userStocks,
            'recommendations' => $recommendations,
            'error' => null,
            'warActive' => $warActive,
        ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('StocksController error: ' . $e->getMessage());
            return view('stocks.index', [
                'error' => 'Error loading stocks: ' . $e->getMessage(),
                'stocks' => [],
                'history' => collect(),
                'userStocks' => [],
                'recommendations' => collect(),
                'warActive' => false,
            ]);
        }
    }
    
    public function update(TornApiService $tornApi)
    {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            return back()->with('error', 'No API key found. Please add your Torn API key in Settings.');
        }

        Cache::forget('stocks_data');
        Cache::forget('user_stocks_' . Auth::id());
        $stocks = $tornApi->getStocks($apiKey);
        
        if (!$stocks) {
            return back()->with('error', 'Failed to fetch stock data. Check your API key.');
        }

        return back()->with('success', 'Stock data updated!');
    }

    public function sync(TornApiService $tornApi)
    {
        $settings = \App\Models\FactionSettings::first();
        $apiKey = $settings?->torn_api_key;
        
        if (!$apiKey) {
            return back()->with('error', 'No faction API key found.');
        }

        Cache::forget('stocks_data');
        $rawStocks = $tornApi->getStocks($apiKey);
        
        if (!$rawStocks) {
            return back()->with('error', 'Failed to fetch stock data.');
        }

        // Cleanup old records (keep max 24 hours)
        \App\Models\StockHistory::where('created_at', '<', now()->subHours(24))->delete();
        
        $now = now();
        foreach ($rawStocks as $stock) {
            \App\Models\StockHistory::updateOrCreate(
                ['stock_id' => $stock['id'] ?? 0],
                [
                    'name' => $stock['name'] ?? '',
                    'acronym' => $stock['acronym'] ?? '',
                    'price' => $stock['market']['price'] ?? 0,
                    'investors' => $stock['market']['investors'] ?? 0,
                    'shares' => $stock['market']['shares'] ?? 0,
                    'market_cap' => $stock['market']['cap'] ?? 0,
                ]
            );
        }

        return back()->with('success', 'Stocks synced and saved to history!');
    }

    private function getApiKey(): ?string
    {
        // First try logged in user's key
        if (Auth::check() && Auth::user()->torn_api_key) {
            return Auth::user()->torn_api_key;
        }
        
        // Fall back to faction settings key (for scheduled tasks)
        $settings = FactionSettings::first();
        return $settings?->torn_api_key;
    }
}
