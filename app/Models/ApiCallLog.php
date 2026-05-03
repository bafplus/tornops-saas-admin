<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCallLog extends Model
{
    protected $table = 'api_call_logs';
    
    protected $fillable = [
        'endpoint',
        'job_command',
        'calls_count',
    ];
    
    public $timestamps = false;
    
    protected $casts = [
        'created_at' => 'datetime',
        'calls_count' => 'integer',
    ];
}