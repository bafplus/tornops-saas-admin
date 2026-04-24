<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        try {
            $settings = \App\Models\FactionSettings::first();
            $warModeEnabled = $settings?->war_mode_enabled ?? false;
            $isWarActive = \App\Models\RankedWar::where('status', 'in progress')->exists();
            $isWarMode = $warModeEnabled || $isWarActive;
            
            $jobs = \App\Models\ScheduledJob::where('enabled', true)->get();
            
            foreach ($jobs as $job) {
                $cron = $isWarMode && $job->war_cron ? $job->war_cron : $job->cron_expression;
                
                if (!$cron) {
                    continue;
                }
                
                $schedule->command($job->command)
                    ->cron($cron)
                    ->withoutOverlapping()
                    ->runInBackground();
            }
        } catch (\Exception $e) {
            // Tables don't exist yet during fresh install
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
