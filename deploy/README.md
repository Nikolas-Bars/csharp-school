# Деплой C# School

Production-стек — Docker Compose на одном сервере. Всё по одному домену:

```
браузер ──https──▶ web (Caddy) ──┬─ /, /assets/*, /runner/*  — файлы SPA и runner
                                 └─ /api/*, /up ──▶ api (RoadRunner + Laravel) ──▶ mariadb, redis
                                                    queue, scheduler — тот же образ API
```

Наружу открыт только `web` (порты 80 и 443). База, Redis и API доступны лишь во внутренней сети Docker.

| Файл | Что это |
| --- | --- |
| `deploy/docker-compose.prod.yml` | Сервисы, тома, ограничения контейнеров |
| `deploy/.env.production.example` | Образец настроек; на сервере копируется в `deploy/.env` (в git не попадает) |
| `backend/.docker/Dockerfile_prod` | Образ API: PHP 8.3 + RoadRunner, без dev-зависимостей, с курсом из `content/` |
| `frontend/.docker/Dockerfile_prod` | Образ веба: сборка runner (.NET SDK) и Vite → Caddy |
| `frontend/.docker/prod/Caddyfile` | HTTPS, кэш, сжатие, заголовки безопасности, прокси `/api` |
| `deploy/check.sh`, `deploy/smoke.sh` | Проверка образов локально / проверка работающего сайта |

Команды — в корневом `Makefile`: `make prod-*` (`make help`).

## Почему так

- **Caddy, а не nginx.** Caddy сам выпускает и продлевает сертификат Let's Encrypt, умеет отдавать
  заранее сжатые `.br`/`.gz` (`precompressed`) — у nginx brotli только сторонним модулем. Один
  бинарник на Go (память защищена), образ ~50 МБ. Он же — HTTPS и обратный прокси: отдельный
  Traefik/nginx перед ним не нужен.
- **Один домен.** API — по `/api` того же домена: не нужны CORS и предварительные запросы
  `OPTIONS`, CSP проще (`connect-src 'self'`). Cookie не используются — вход по Bearer-токену.
- **Курс внутри образа API.** `content/` копируется в образ (`/var/www/content`) без эталонных и
  неверных решений — они нужны только тестам runner. Версия курса = версия образа.
- **Runner собирается в образе веба** из исходников (`dotnet publish`, как `make build` в runner):
  на сервере не нужны .NET и заранее собранные файлы.

## Сервер

- Linux с Docker Engine 24+ и Compose v2.24+. 2 ядра и 4 ГБ памяти (сборка runner требовательна;
  можно собирать в другом месте, см. «Образы на другой машине»).
- Домен: DNS-запись A (и AAAA, если есть IPv6) указывает на сервер. Открыты 80/tcp, 443/tcp,
  443/udp (HTTP/3).

## Первый запуск

```bash
git clone https://github.com/Nikolas-Bars/csharp-school.git && cd csharp-school
cp deploy/.env.production.example deploy/.env
```

В `deploy/.env` заполнить (секреты — только здесь):

- `SITE_ADDRESS` и `APP_URL` — домен; `ACME_EMAIL` — почта для Let's Encrypt;
- `APP_KEY` — `echo "base64:$(openssl rand -base64 32)"`;
- `DB_PASSWORD`, `REDIS_PASSWORD` — `openssl rand -hex 32` (без `$`, пробелов и кавычек);
- `IMAGE_TAG` — версия, например короткий коммит: `git rev-parse --short HEAD`;
- почта (`MAIL_*`), когда будет почтовый сервис.

```bash
chmod 600 deploy/.env
make prod-deploy                      # сборка → миграции и курс → запуск
deploy/smoke.sh https://<домен>       # проверка снаружи
```

## Порядок деплоя

`make prod-deploy` = три шага, их можно выполнять и по отдельности:

1. `make prod-build` — собрать образы `csharp-school-api:<IMAGE_TAG>` и `csharp-school-web:<IMAGE_TAG>`.
   Работающий сайт не затрагивается.
2. `make prod-migrate` — разовыми контейнерами нового образа: `migrate --force`, затем
   `course:import`. Пока идут миграции, работает старая версия API — поэтому миграции только
   совместимые со старым кодом: новые таблицы и столбцы, а удаление — в следующем релизе
   (так же, как «ломающие изменения API — новым полем»).
3. `make prod-up` — пересоздать контейнеры с новым тегом. Compose ждёт healthcheck (`--wait`).

## Обновление

```bash
git pull
sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=$(git rev-parse --short HEAD)/" deploy/.env
make prod-deploy
```

Только учебный контент поменялся — то же самое: курс едет внутри образа API.

## Откат

Предыдущие образы остаются на сервере (`docker image ls csharp-school-api`).

```bash
sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=<прошлый тег>/" deploy/.env
make prod-up
```

Миграции сами не откатываются. Если новая миграция мешает старому коду — откатить её **до**
смены тега, новым образом: `docker compose -f deploy/docker-compose.prod.yml run --rm api php artisan migrate:rollback --step=1`.
Курс прошлой версии: `docker compose -f deploy/docker-compose.prod.yml run --rm api php artisan course:import`
после смены тега.

## Резервные копии

- База — от пользователя приложения (пароль root случайный, он не нужен):

  ```bash
  docker compose -f deploy/docker-compose.prod.yml exec -T mariadb \
    sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" --single-transaction "$MARIADB_DATABASE"' \
    | gzip > backup-$(date +%F).sql.gz
  ```

- Том `caddy_data` — сертификаты: потеря не страшна, но выпуск заново ограничен лимитами Let's Encrypt.
- Redis — кэш, очередь и лимиты попыток; терять неприятно, но не критично.

## Как устроено

### Кэш и сжатие (Caddy)

| Что | Cache-Control | Сжатие |
| --- | --- | --- |
| `/assets/*` (Vite, хеш в имени) | `public, max-age=31536000, immutable` | готовые `.br`/`.gz` из сборки |
| `/runner/_framework/*.<отпечаток>.wasm/js` | `public, max-age=31536000, immutable` | готовые `.br`/`.gz` от `dotnet publish` |
| `/runner/_framework/dotnet.js` (без отпечатка, в нём список файлов) | `no-cache` | `.br`/`.gz` |
| `index.html` и маршруты SPA | `no-cache` | `.br`/`.gz` |
| `/api/*` | от Laravel | zstd/gzip на лету (ответы маленькие) |

Большие файлы runner на лету не сжимаются никогда. Несуществующий файл в `/assets` или `/runner`
— 404, а не `index.html` (иначе вместо `.wasm` пришла бы страница). `.wasm` отдаётся как
`application/wasm` (нужно для потоковой компиляции).

### Заголовки безопасности

HSTS, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`,
`Cross-Origin-Opener-Policy`, `Permissions-Policy`, без `Server`. CSP:

- `script-src 'self' 'wasm-unsafe-eval' 'sha256-…'` — .NET в WebAssembly компилирует `.wasm`
  (`'wasm-unsafe-eval'`, а не `'unsafe-eval'`: JavaScript через eval не выполняется); хеш —
  встроенного скрипта темы в `index.html`, считается при сборке образа (`csp-hashes.mjs`);
- `worker-src 'self'` — Web Worker runner; `connect-src 'self'` — загрузка `.wasm` и `/api`;
- `style-src 'unsafe-inline'` — CodeMirror добавляет стили элементом `<style>`;
- `font-src 'self'` — шрифты свои, сторонних доменов в CSP нет.

После изменения CSP — открыть урок с задачей и запустить код: в консоли браузера не должно быть
нарушений CSP.

### Laravel за прокси

- `TRUSTED_PROXIES` (по умолчанию — частные подсети, где живёт сеть Docker) →
  `config/trustedproxy.php`: от этих адресов API принимает `X-Forwarded-For/Host/Port/Proto` —
  видит настоящий IP ученика (лимиты попыток входа) и `https` в ссылках. Тест — `TrustProxiesTest`.
- Caddy, стоящий первым, подделанные клиентом `X-Forwarded-*` отбрасывает и ставит свои.
  Перед Caddy есть балансировщик хостинга — его подсеть в `CADDY_TRUSTED_PROXIES`.
- `CORS_ALLOWED_ORIGINS` пусто: фронт и API на одном домене.

### Контейнеры

- Все процессы не от root: API — `www-data`, Caddy — свой пользователь (порты 80/443 —
  по capability бинарника), Redis — `redis`.
- Файловая система только для чтения; писать можно в tmpfs (`storage`, `bootstrap/cache`, `/tmp`)
  — узел без состояния: файлы пользователей, когда появятся, — во внешнее хранилище
  (`Storage::disk('s3')`), не на диск контейнера.
- Кэши конфигурации, маршрутов и событий Laravel — при старте контейнера (`entrypoint.sh`):
  в них попадает окружение, которого при сборке нет.
- OPcache включён (и для CLI — воркеры RoadRunner), `validate_timestamps=0`.
- Журналы — в stdout/stderr, ротация json-file (5 × 10 МБ на сервис): `make prod-logs s=api`.

### За внешним прокси с TLS

Если HTTPS держит балансировщик хостинга: `SITE_ADDRESS=:80`, его подсеть — в
`CADDY_TRUSTED_PROXIES` (тогда Caddy передаст API его `X-Forwarded-Proto: https`).

### Образы на другой машине

`make prod-build` на любой машине, затем перенос: `docker save csharp-school-api:<тег>
csharp-school-web:<тег> | ssh server docker load`. Реестр образов (GHCR) — когда понадобится:
`IMAGE_PREFIX=ghcr.io/<владелец>/csharp-school`.

## Проверка на своей машине

```bash
make prod-check
```

`deploy/check.sh` собирает образы с тегом `check`, поднимает стек отдельным проектом
`csharp-school-prod-check` на http://localhost:15390 (без HTTPS), выполняет миграции и
`course:import`, запускает `deploy/smoke.sh`, а затем удаляет контейнеры, тома и образы. Стек
разработки (`make up`) не затрагивается. То же выполняет CI (`.github/workflows/deploy.yml`).
