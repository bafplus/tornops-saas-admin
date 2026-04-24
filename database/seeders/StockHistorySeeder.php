<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockHistory;

class StockHistorySeeder extends Seeder
{
    public function run(): void
    {
        $stocks = [
            ['id' => 1, 'name' => 'Torn & Shanghai Banking', 'acronym' => 'TSB'],
            ['id' => 2, 'name' => 'Torn City Investments', 'acronym' => 'TCI'],
            ['id' => 3, 'name' => 'Torn News Network', 'acronym' => 'TNN'],
            ['id' => 4, 'name' => 'Torn Arms Dealers', 'acronym' => 'TAD'],
            ['id' => 5, 'name' => 'Torn Pharmaceuticals', 'acronym' => 'TPH'],
            ['id' => 6, 'name' => 'Torn Construction', 'acronym' => 'TCC'],
            ['id' => 7, 'name' => 'Fishing Holdings Group', 'acronym' => 'FHG'],
            ['id' => 8, 'name' => 'McAfee Corporation', 'acronym' => 'MCS'],
            ['id' => 9, 'name' => 'Torn Clothing Co', 'acronym' => 'TCC'],
            ['id' => 10, 'name' => 'Lava Tooth Corp', 'acronym' => 'LTC'],
            ['id' => 11, 'name' => 'Torn Electrical Group', 'acronym' => 'TEG'],
            ['id' => 12, 'name' => 'Oil Refinery Corp', 'acronym' => 'ORC'],
        ];

        $basePrice = [
            1 => 1200, 2 => 850, 3 => 320, 4 => 450, 5 => 280,
            6 => 190, 7 => 520, 8 => 380, 9 => 150, 10 => 220,
            11 => 170, 12 => 410,
        ];

        $baseInvestors = [
            1 => 21000, 2 => 18000, 3 => 12000, 4 => 15000, 5 => 9000,
            6 => 7000, 7 => 14000, 8 => 11000, 9 => 5000, 10 => 8000,
            11 => 6000, 12 => 10000,
        ];

        // Generate 7 days of history
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day)->toDateString();
            
            foreach ($stocks as $stock) {
                $priceVariation = 0.9 + (mt_rand(0, 200) / 1000); // 0.9 to 1.1
                $investorVariation = 0.95 + (mt_rand(0, 100) / 1000); // 0.95 to 1.05
                
                $price = round($basePrice[$stock['id']] * $priceVariation, 2);
                $investors = (int)($baseInvestors[$stock['id']] * $investorVariation);
                $shares = $investors * mt_rand(800, 1200);
                $marketCap = $price * $shares;

                StockHistory::updateOrCreate(
                    ['stock_id' => $stock['id'], 'recorded_at' => $date],
                    [
                        'name' => $stock['name'],
                        'acronym' => $stock['acronym'],
                        'price' => $price,
                        'investors' => $investors,
                        'shares' => $shares,
                        'market_cap' => $marketCap,
                    ]
                );
            }
        }

        $this->command->info('Stock history seeded with 7 days of sample data.');
    }
}
