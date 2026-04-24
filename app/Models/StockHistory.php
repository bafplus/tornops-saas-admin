<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $table = 'stock_history';
    protected $fillable = [
        'stock_id',
        'name',
        'acronym',
        'price',
        'investors',
        'shares',
        'market_cap',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'investors' => 'integer',
        'shares' => 'integer',
        'market_cap' => 'decimal:2',
        'recorded_at' => 'date',
    ];
}
