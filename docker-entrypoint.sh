#!/bin/sh
set -e

# Railway sets $PORT dynamically. Reconfigure Apache to listen on it.
# Falls back to 80 if not set (local Docker testing).
PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

exec "$@"
