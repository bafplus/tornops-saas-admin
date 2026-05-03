<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduledJob;
use App\Models\RankedWar;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $isWarMode = RankedWar::where('status', 'in progress')->exists();
        
        $jobs = ScheduledJob::where('enabled', true)->get();
        
        foreach ($jobs as $job) {
            $shouldSchedule = $this->shouldScheduleJob($job, $isWarMode);
            
            if (!$shouldSchedule) {
                continue;
            }
            
            $cron = $this->getEffectiveCron($job, $isWarMode);
            
            if (!$cron) {
                continue;
            }
            
            $scheduledCommand = $schedule->command($job->command)
                ->cron($cron)
                ->withoutOverlapping()
                ->runInBackground();
            
            if ($job->war_mode_only) {
                $scheduledCommand->when(function() use ($isWarMode) {
                    return $isWarMode;
                });
            }
        }
    }
    
    private function shouldScheduleJob(ScheduledJob $job, bool $isWarMode): bool
    {
        if ($job->war_mode_only && !$isWarMode) {
            return false;
        }
        
        return true;
    }
    
    private function getEffectiveCron(ScheduledJob $job, bool $isWarMode): ?string
    {
        if ($job->war_mode_only && $isWarMode && $job->war_cron) {
            return $job->war_cron;
        }
        
        return $job->cron_expression;
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
