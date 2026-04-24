#!/bin/bash
set -e

echo "=== TornOps Container Starting ==="

DATA_DIR="${DATA_DIR:-/data}"

git config --global --add safe.directory /var/www/html

# Ensure /var/www has correct ownership for SQLite
chown www-data:www-data /var/www

# Clone or pull repository directly to /var/www/html
if [ -d "/var/www/html/.git" ]; then
    echo "Updating repository..."
    cd /var/www/html
    # Switch to HTTPS if needed
    git remote set-url origin https://github.com/bafplus/tornops.git || true
    # Save any local changes, pull, then restore
    git stash push --include-untracked -m "temp_stash" || true
    git pull origin main || true
    git stash pop || true
else
    echo "First run: Cloning repository..."
    if [ -d "/data" ]; then
        git clone https://github.com/bafplus/tornops.git /var/www/html
    else
        git clone https://github.com/bafplus/tornops.git /var/www/html
    fi
    cd /var/www/html
fi

# Always work in /var/www/html for the web app
cd /var/www/html

# Create storage directories that are gitignored (with subdirectories)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views

# Fix permissions for Apache - ensure all files are readable and executable
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 777 storage storage/framework storage/logs bootstrap/cache
chown -R www-data:www-data .

chmod -R 777 storage/framework
chown -R www-data:www-data storage/framework

mkdir -p storage/framework/cache/data
chmod -R 777 storage/framework/cache/data

# Ensure external /data directory exists for fresh installs
mkdir -p /data
chmod 777 /data

# Fix /var/www permissions LAST (before supervisord starts)
chown -R www-data:www-data /var/www
chmod -R 777 /var/www
if [ -d "$DATA_DIR" ]; then
    echo "Fixing /data permissions..."
    chown -R 33:33 "$DATA_DIR"
    chmod -R 777 "$DATA_DIR"
fi

# Use /data/.env if mounted, or use environment variables passed to container
if [ -d "$DATA_DIR" ]; then
    if [ -f "${DATA_DIR}/.env" ]; then
        echo "Using .env from data directory..."
        cp "${DATA_DIR}/.env" .env
        
        # Force correct database path for /data volume
        sed -i 's|DB_DATABASE=.*|DB_DATABASE=/data/database.sqlite|' .env
        
        grep -q "^SESSION_DRIVER=" .env || echo "SESSION_DRIVER=file" >> .env
        grep -q "^CACHE_STORE=" .env || echo "CACHE_STORE=array" >> .env
    else
        echo "Using environment variables with /data volume..."
        # Create .env from environment variables but use /data for database
        cat > .env << EOF
APP_NAME="${APP_NAME:-TornOps}"
APP_ENV="${APP_ENV:-production}"
APP_KEY=
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost:8080}"
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=array
TORN_API_KEY=${TORN_API_KEY:-dummy}
FACTION_ID=${FACTION_ID:-}
EOF
    fi
    
    DB_PATH="/data/database.sqlite"
else
    echo "Using environment variables..."
    # Use env vars passed to container
    cat > .env << EOF
APP_NAME="${APP_NAME:-TornOps}"
APP_ENV="${APP_ENV:-production}"
APP_KEY=
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost:8080}"
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=array
TORN_API_KEY=${TORN_API_KEY:-dummy}
EOF
    DB_PATH="/var/www/html/database.sqlite"
fi

if [ ! -f "$DB_PATH" ]; then
    echo "Creating database at $DB_PATH..."
    touch "$DB_PATH"
    chmod 666 "$DB_PATH"
    chown 33:33 "$DB_PATH"
fi

chmod 666 "$DB_PATH"
chown 33:33 "$DB_PATH"

# Install dependencies
echo "Installing dependencies..."
composer install --no-interaction --no-dev --ignore-platform-reqs --no-scripts || composer update --no-interaction --no-dev --no-scripts

# Now run post-install scripts (safe after DB exists)
echo "Running post-install..."
php artisan key:generate --force 2>/dev/null || true
php artisan migrate --force
php artisan cache:clear
php artisan jobs:seed

# Skip package:discover - not needed and can fail

# Set Apache to run as www-data
export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data

php artisan key:generate --force 2>/dev/null || true
php artisan migrate --force
php artisan cache:clear
php artisan jobs:seed

# Ensure /data directory and database are writable by www-data (fix volume permissions)
if [ -d "/data" ]; then
    chown -R www-data:www-data /data
    chmod -R 775 /data
    if [ -f "/data/database.sqlite" ]; then
        chown www-data:www-data /data/database.sqlite
        chmod 664 /data/database.sqlite
    fi
fi

# Setup cron
# During active war: war syncs every minute, non-essential syncs disabled
# Normal: faction sync every 10 min, stocks every 10 min
sleep 5

# Copy war-sync wrapper script (always fresh copy)
rm -f /usr/local/bin/torn-sync-wrapper.sh
cp /var/www/html/docker/war-sync.sh /usr/local/bin/torn-sync-wrapper.sh
chmod +x /usr/local/bin/torn-sync-wrapper.sh

# Main sync wrapper (runs every minute)
rm -f /etc/cron.d/tornops-sync
echo "* * * * * root /usr/local/bin/torn-sync-wrapper.sh >> /dev/null 2>&1" > /etc/cron.d/tornops-sync

# Backup: ensure war syncs run if wrapper fails (every minute)
echo "* * * * * root /usr/local/bin/php /var/www/html/artisan torn:sync-active >> /dev/null 2>&1" >> /etc/cron.d/tornops-sync

chmod 644 /etc/cron.d/tornops-sync
service cron reload

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf