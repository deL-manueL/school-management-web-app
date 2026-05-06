#!/bin/sh
set -e

PORT="${PORT:-80}"

echo "Starting with PORT=${PORT}"

# Patch ports.conf — replace any Listen line
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

# Patch the default vhost
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Remove conflicting MPM modules at runtime
rm -f /etc/apache2/mods-enabled/mpm_event.load \
       /etc/apache2/mods-enabled/mpm_event.conf \
       /etc/apache2/mods-enabled/mpm_worker.load \
       /etc/apache2/mods-enabled/mpm_worker.conf

# Verify exactly one MPM is loaded
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
if [ "$MPM_COUNT" -ne 1 ]; then
  echo "FATAL: Expected 1 MPM, found ${MPM_COUNT}"
  exit 1
fi

echo "MPM OK: $(ls /etc/apache2/mods-enabled/mpm_*.load)"
echo "Apache will listen on port ${PORT}"

exec "$@"
