<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class DisableCsrfForMasterKey extends Middleware
{
    protected function inExceptArray()
    {
        $except = parent::inExceptArray();
        
        // Add master key login endpoint - exempt from CSRF
        $except[] = 'login*master_key*';
        
        return $except;
    }
}