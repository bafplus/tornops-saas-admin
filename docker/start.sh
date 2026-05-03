#!/bin/bash
set -e

echo "=== TornOps Container Starting ==="

echo "Running database initialization..."
/usr/local/bin/init-db.sh

echo "Setting up Laravel scheduler cron..."
echo "* * * * * root /usr/local/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
