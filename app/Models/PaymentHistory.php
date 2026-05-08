<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';

    protected $fillable = [
        'faction_id', 'event_id', 'item_name', 'quantity', 'description',
        'payer_name', 'payer_id', 'extension_days', 'expires_at',
        'matched_instance', 'manual', 'raw_event',
    ];

    protected $casts = [
        'matched_instance' => 'boolean',
        'manual' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function faction()
    {
        return $this->belongsTo(Faction::class);
    }
}
