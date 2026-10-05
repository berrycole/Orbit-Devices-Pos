#!/bin/sh
set -eu
mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar
chown -R www-data:www-data writable
chmod -R u+rwX,g+rwX writable
php spark migrate --all
if [ -n "${ORBIT_ADMIN_PASSWORD:-}" ]; then
    php spark db:seed DemoSeeder
fi
exec apache2-foreground
