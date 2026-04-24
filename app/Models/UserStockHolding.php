<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStockHolding extends Model
{
    protected $table = 'user_stock_holdings';
    
    protected $fillable = [
        'stock_id',
        'name',
        'acronym',
        'shares',
        'avg_price',
        'current_price',
        'value',
        'profit_loss',
        'profit_loss_pct',
        'bonus',
        'user_id',
        'recorded_at',
    ];

    protected $casts = [
        'shares' => 'integer',
        'avg_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'value' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'profit_loss_pct' => 'decimal:2',
        'bonus' => 'array',
        'recorded_at' => 'date',
    ];
}
