<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
        'sub_type',
        'is_tradable',
        'is_found_in_city',
        'buy_price',
        'sell_price',
        'market_price',
        'circulation',
        'image',
        'last_synced_at',
    ];

    protected $casts = [
        'is_tradable' => 'boolean',
        'is_found_in_city' => 'boolean',
        'buy_price' => 'integer',
        'sell_price' => 'integer',
        'market_price' => 'integer',
        'circulation' => 'integer',
        'last_synced_at' => 'datetime',
    ];
}
