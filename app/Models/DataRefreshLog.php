<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRefreshLog extends Model
{
    protected $fillable = [
        'data_type',
        'status',
        'records_updated',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function logStart(string $dataType): self
    {
        return self::create([
            'data_type' => $dataType,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markComplete(int $records = 0): void
    {
        $this->update([
            'status' => 'completed',
            'records_updated' => $records,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }
}
