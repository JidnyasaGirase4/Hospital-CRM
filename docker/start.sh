#!/bin/sh
set -e
sed -i "s/Listen 80/Listen ${PORT:-80}/; s/:80>/:${PORT:-80}>/" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf
[ -n "$APP_KEY" ] || export APP_KEY=$(php artisan key:generate --show)
php artisan config:cache
php artisan migrate --force
if [ "${SEED_DEMO:-false}" = "true" ]; then php artisan db:seed --force || true; fi
exec apache2-foreground
