#!/bin/sh
set -e

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Remove conflicting MPM modules at runtime, after all mounts are applied.
# Railway may bind-mount the apache mods directory, restoring mpm_event.load
# after the image build. Deleting here is the only layer that survives that.
rm -f /etc/apache2/mods-enabled/mpm_event.load \
       /etc/apache2/mods-enabled/mpm_event.conf \
       /etc/apache2/mods-enabled/mpm_worker.load \
       /etc/apache2/mods-enabled/mpm_worker.conf

# Verify exactly one MPM is loaded before handing off to Apache
MPM_COUNT=$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null | wc -l)
if [ "$MPM_COUNT" -ne 1 ]; then
  echo "FATAL: Expected 1 MPM, found ${MPM_COUNT}: $(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null)"
  exit 1
fi

echo "MPM OK: $(ls /etc/apache2/mods-enabled/mpm_*.load)"

exec "$@"
