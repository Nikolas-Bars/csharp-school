#!/usr/bin/env bash
# Проверка production-образов на своей машине или в CI, не трогая стек разработки:
# собрать образы с тегом CHECK_TAG, поднять стек отдельным проектом Docker на свободных портах
# (без HTTPS: SITE_ADDRESS=:80), миграции и course:import, deploy/smoke.sh, затем всё удалить —
# контейнеры, тома, временный .env и образы (KEEP_IMAGES=1 — образы оставить).
# Запуск: make prod-check  (или deploy/check.sh)
set -euo pipefail

cd "$(dirname "$0")"

PROJECT=${CHECK_PROJECT:-csharp-school-prod-check}
TAG=${CHECK_TAG:-check}
PORT=${CHECK_HTTP_PORT:-15390}
TLS_PORT=${CHECK_HTTPS_PORT:-15391}

env_file=$(mktemp)
compose() {
    ENV_FILE="$env_file" docker compose -p "$PROJECT" -f docker-compose.prod.yml --env-file "$env_file" "$@"
}

cleanup() {
    status=$?
    if ((status != 0)); then
        echo "== Ошибка — журналы сервисов:"
        compose logs --tail 40 || true
    fi
    echo "== Остановка и удаление проверочного стека"
    compose down -v --remove-orphans || true
    if [[ "${KEEP_IMAGES:-0}" != 1 ]]; then
        docker image rm "csharp-school-api:$TAG" "csharp-school-web:$TAG" > /dev/null 2>&1 || true
    fi
    rm -f "$env_file"
    exit "$status"
}
trap cleanup EXIT

# Настройки — из образца, секреты — случайные на один запуск
sed -e 's|^SITE_ADDRESS=.*|SITE_ADDRESS=:80|' \
    -e 's|^ACME_EMAIL=.*|ACME_EMAIL=check@example.org|' \
    -e "s|^HTTP_PORT=.*|HTTP_PORT=$PORT|" \
    -e "s|^HTTPS_PORT=.*|HTTPS_PORT=$TLS_PORT|" \
    -e "s|^IMAGE_TAG=.*|IMAGE_TAG=$TAG|" \
    -e "s|^APP_URL=.*|APP_URL=http://localhost:$PORT|" \
    -e "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" \
    -e "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$(openssl rand -hex 24)|" \
    .env.production.example > "$env_file"

echo "== Режим общего сервера (docker-compose.edge.yml): файл собирается, у web нет портов"
edge_config=$(ENV_FILE="$env_file" docker compose -p "$PROJECT" -f docker-compose.prod.yml -f docker-compose.edge.yml \
    --env-file "$env_file" config --format json)
if ! grep -q '"csharp-school-web"' <<< "$edge_config" || docker compose -p "$PROJECT" -f docker-compose.prod.yml \
    -f docker-compose.edge.yml --env-file "$env_file" config | sed -n '/^  web:/,/^  [a-z]*:$/p' | grep -q 'published:'; then
    echo "docker-compose.edge.yml: у web должны быть сеть edge с именем csharp-school-web и ни одного порта" >&2
    exit 1
fi

echo "== Сборка образов (тег $TAG)"
compose build

echo "== Миграции и загрузка курса"
compose run --rm api php artisan migrate --force
compose run --rm api php artisan course:import

echo "== Запуск стека $PROJECT на http://localhost:$PORT"
compose up -d --wait

echo "== Размеры образов"
docker image ls --format '{{.Repository}}:{{.Tag}}  {{.Size}}' | grep -E "^csharp-school-(api|web):$TAG " || true

echo "== Процессы — не root"
for service in api queue scheduler web; do
    printf '  %-9s uid=%s\n' "$service" "$(compose exec -T "$service" id -u)"
done

echo "== Проверка снаружи"
./smoke.sh "http://localhost:$PORT"

# CHECK_E2E=1 — ещё и в настоящем браузере (frontend/e2e): Chromium из официального образа
# Playwright той же версии, что в package.json. Контейнер живёт в сети web: сайт для него —
# http://localhost, как у ученика (безопасный контекст), с тем же Caddy, CSP и сжатием
if [[ "${CHECK_E2E:-0}" == 1 ]]; then
    playwright=$(sed -n 's/.*"@playwright\/test": "\([0-9.]*\)".*/\1/p' ../frontend/package.json)
    : "${playwright:?в frontend/package.json нет точной версии @playwright/test (без ^ и ~)}"
    # Отчёты (трассировка упавшего теста) пишутся внутри контейнера и копируются сюда с правами
    # текущего пользователя: в смонтированный репозиторий root ничего не пишет
    results=${CHECK_E2E_RESULTS:-$PWD/../frontend/test-results}
    rm -rf "$results" && mkdir -p "$results"
    echo "== Проверка в браузере: Playwright $playwright, Chromium (отчёты — $results)"
    docker run --rm --init --ipc=host --network "container:$(compose ps -q web)" \
        -v "$PWD/..":/repo -v /repo/frontend/node_modules -v csharp-school-npm:/root/.npm \
        -v "$results":/results -w /repo/frontend \
        -e CI -e E2E_BASE_URL=http://localhost -e E2E_OUTPUT_DIR=/tmp/test-results \
        -e OWNER="$(id -u):$(id -g)" \
        "mcr.microsoft.com/playwright:v$playwright-noble" \
        sh -c 'npm ci --no-audit --no-fund --loglevel=error && npm run test:e2e; status=$?
            cp -R /tmp/test-results/. /results/ 2> /dev/null; chown -R "$OWNER" /results; exit $status'
fi

echo "== Память после проверки (лимиты — docker-compose.prod.yml)"
docker stats --no-stream --format '  {{.Name}}\t{{.MemUsage}}' $(compose ps -q)
