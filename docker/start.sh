#!/bin/bash
set -e

echo "=== TornOps Admin Starting ==="

# Fix Docker socket permissions: match host's docker group GID
if [ -S "/var/run/docker.sock" ]; then
    DOCKER_GID=$(stat -c '%g' /var/run/docker.sock 2>/dev/null)
    if [ -n "$DOCKER_GID" ] && [ "$DOCKER_GID" != "0" ]; then
        EXISTING_GROUP=$(getent group "$DOCKER_GID" | cut -d: -f1)
        if [ -z "$EXISTING_GROUP" ]; then
            groupadd -g "$DOCKER_GID" dockerhost 2>/dev/null
            usermod -aG dockerhost www-data 2>/dev/null
            echo "Docker socket GID=$DOCKER_GID: created dockerhost group"
        else
            usermod -aG "$EXISTING_GROUP" www-data 2>/dev/null
            echo "Docker socket GID=$DOCKER_GID: added www-data to $EXISTING_GROUP"
        fi
        newgrp - www-data 2>/dev/null || true
    fi
fi

# Start cron daemon for Laravel scheduler
echo "Starting cron daemon..."
service cron start 2>/dev/null || cron 2>/dev/null || true

# Start Apache in foreground (replaces PID 1)
echo "Starting Apache..."
exec apache2-foreground
