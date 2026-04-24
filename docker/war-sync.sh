#!/bin/bash
# TornOps scheduler - reads schedule from database and respects intervals
# Only uses war_cron when there's an ACTIVE war (not just war mode setting)

PHP="/usr/local/bin/php"
ARTISAN="/var/www/html/artisan"
LOG="/dev/null"

# Check for ACTIVE war only (not the war mode setting)
WAR_ACTIVE=$($PHP $ARTISAN tinker --execute="echo App\Models\RankedWar::where('status', 'in progress')->exists() ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')

IS_WAR_ACTIVE="0"
if [ "$WAR_ACTIVE" = "1" ]; then
    IS_WAR_ACTIVE="1"
fi

# Get all enabled jobs from database
JOBS_DATA=$($PHP $ARTISAN tinker --execute="
\$jobs = App\Models\ScheduledJob::where('enabled', true)->get();
\$isWarActive = App\Models\RankedWar::where('status', 'in progress')->exists();

foreach (\$jobs as \$j) {
    // Use war_cron only if there's an active war, otherwise use cron_expression
    \$cron = \$isWarActive && \$j->war_cron ? \$j->war_cron : \$j->cron_expression;
    
    // Parse interval from cron
    \$interval = 60; // default 1 minute
    if (!\$cron || \$cron === '') {
        // Skip if no cron set (Never/disabled)
        echo 'SKIP';
    } else if (preg_match('/^\*\/(\d+)\s+\*/', \$cron, \$m)) {
        \$interval = (int)\$m[1] * 60;
    } else if (\$cron === '0 * * * *') {
        \$interval = 3600;
    } else if (str_starts_with(\$cron, '0 0')) {
        \$interval = 86400;
    } else {
        echo 'SKIP';
    }
    
    // Get last run time
    \$lastRun = \$j->last_run_at ? \$j->last_run_at->timestamp : 0;
    \$now = time();
    \$elapsed = \$now - \$lastRun;
    
    echo \$j->command . '|' . \$interval . '|' . \$lastRun . PHP_EOL;
}
" 2>/dev/null)

# Parse jobs and run if interval has passed
echo "$JOBS_DATA" | while IFS='|' read -r cmd interval lastRun; do
    [ -z "$cmd" ] && continue
    [ "$interval" = "SKIP" ] && continue
    
    CURRENT_TIME=$(date +%s)
    ELAPSED=$((CURRENT_TIME - lastRun))
    
    if [ $ELAPSED -ge $interval ]; then
        # Run the command
        $PHP $ARTISAN $cmd >> $LOG 2>&1
        
        # Update last_run_at in database
        CMD_BASE=$(echo $cmd | sed 's/torn://')
        $PHP $ARTISAN tinker --execute="
\$j = App\Models\ScheduledJob::where('command', '$cmd')->first();
if (\$j) {
    \$j->last_run_at = now();
    \$j->save();
}
" >> $LOG 2>&1
    fi
done
