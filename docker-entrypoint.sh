#!/bin/sh
set -e

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Final MPM sanity check — abort loudly if conflict still exists
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
if [ "$MPM_COUNT" -gt 1 ]; then
  echo "FATAL: Multiple MPM modules enabled: $(ls /etc/apache2/mods-enabled/mpm_*.load)"
  exit 1
fi

exec "$@"
