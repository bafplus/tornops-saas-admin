<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jobs are scheduled via cron in /etc/cron.d/tornops-sync
// sync-faction runs every 5 min (calls sync-members + sync-wars)
// sync-active runs every 1 min for real-time war updates
