#!/bin/sh
# 1. Force create directories and apply correct ownership/permissions at runtime
mkdir -p storage/app/public/profiles \
         storage/app/public/profiles/thumbnails \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan storage:link --relative --force

php artisan config:cache
php artisan route:cache

php artisan migrate --force

php artisan db:seed --force &

exec docker-php-serversideup-entrypoint
