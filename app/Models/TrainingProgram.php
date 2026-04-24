<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    protected $table = 'training_programs';
    
    protected $fillable = [
        'name',
        'str_percent',
        'def_percent',
        'spd_percent',
        'dex_percent',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'training_program_id');
    }
}