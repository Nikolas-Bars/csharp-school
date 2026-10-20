#!/bin/sh
# Старт production-контейнера (api, queue, scheduler и разовые artisan-команды).
# Кэши конфигурации, маршрутов и событий собираются здесь, а не при сборке образа: в конфигурацию
# попадают переменные окружения (секреты), а они есть только при запуске.
set -eu

cd /var/www/html

# В docker-compose.prod.yml storage и bootstrap/cache — tmpfs (файловая система только для чтения):
# папки создаются заново при каждом старте
mkdir -p storage/app/private storage/framework/cache/data storage/framework/views \
    storage/framework/sessions storage/logs bootstrap/cache

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction

exec "$@"
