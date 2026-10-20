#!/usr/bin/env bash
# Обновление на сервере (deploy/README.md): свежие файлы deploy/ из git, готовые образы из GHCR
# (их собирает GitHub Actions после мёржа в main — на сервере ничего не собирается: сборке runner
# нужно 2+ ГБ памяти), миграции и курс, перезапуск. Режим и адреса — из deploy/.env (COMPOSE_FILE,
# IMAGE_PREFIX, IMAGE_TAG).
#
#   /opt/csharp-school/deploy/deploy.sh            — тег из IMAGE_TAG в .env (обычно latest)
#   /opt/csharp-school/deploy/deploy.sh <sha>      — конкретный коммит main (откат — прошлый)
set -euo pipefail

# Пути в COMPOSE_FILE — от текущей папки: команды только отсюда
cd "$(dirname "$0")"

if [[ ! -f .env ]]; then
    echo "Нет deploy/.env — образец: .env.production.example (deploy/README.md)" >&2
    exit 1
fi

echo "== Код deploy/ из main"
git -C .. pull --ff-only

if [[ -n "${1:-}" ]]; then
    # Переменная окружения важнее значения в .env
    export IMAGE_TAG="$1"
fi

echo "== Образы (тег ${IMAGE_TAG:-из .env})"
docker compose pull --quiet

echo "== Миграции и курс"
docker compose run --rm api php artisan migrate --force
docker compose run --rm api php artisan course:import

echo "== Запуск"
docker compose up -d --wait --remove-orphans

# Старые образы C# School — чтобы не копились на диске (тома и чужие образы не трогаются)
docker image prune -f --filter "label=org.opencontainers.image.title=csharp-school" > /dev/null || true

docker compose ps
