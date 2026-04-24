#!/bin/bash
set -e

echo "Initializing TornOps..."

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Installing MariaDB database..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

if [ ! -f "/var/lib/mysql/.initialized" ]; then
    echo "Setting up database and user..."
    
    /usr/bin/mysqld_safe --datadir=/var/lib/mysql &
    
    MYSQL_PID=$!
    
    for i in {1..30}; do
        if mysql -u root -e "SELECT 1" &>/dev/null; then
            break
        fi
        sleep 1
    done
    
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS tornops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'tornops'@'localhost' IDENTIFIED BY 'tornops123';
CREATE USER IF NOT EXISTS 'tornops'@'127.0.0.1' IDENTIFIED BY 'tornops123';
GRANT ALL PRIVILEGES ON tornops.* TO 'tornops'@'localhost';
GRANT ALL PRIVILEGES ON tornops.* TO 'tornops'@'127.0.0.1';
SET GLOBAL time_zone = '+00:00';
FLUSH PRIVILEGES;
EOF
    
    touch /var/lib/mysql/.initialized
    
    kill $MYSQL_PID 2>/dev/null || true
    wait $MYSQL_PID 2>/dev/null || true
fi

echo "MariaDB initialization complete."
