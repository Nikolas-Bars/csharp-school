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
| `deploy/docker-compose.edge.yml` | Режим общего сервера: без портов, за Caddy мессенджера по сети `edge` |
| `deploy/deploy.sh` | Обновление на сервере: образы из GHCR, миграции, курс, перезапуск |
| `deploy/check.sh`, `deploy/smoke.sh` | Проверка образов локально / проверка работающего сайта |

Команды — в корневом `Makefile`: `make prod-*` (`make help`; `EDGE=1` — режим общего сервера).

## Сейчас: сервер мессенджера — https://csharp.call-yansburg.com

Своего сервера у C# School пока нет: стек живёт на сервере мессенджера Kolyansburg
(`185.17.1.109`, `ssh starter`, Ubuntu 24.04, 4 vCPU, 2.9 ГБ памяти) в `/opt/csharp-school`, рядом
с мессенджером и Inkroom, со своими базой и Redis. Так же устроен Inkroom
(`artverse-backend/deploy/README.md`).

```
браузер ──https──▶ Caddy мессенджера ──http, сеть edge──▶ csharp-school-web (наш Caddy, :80) ──▶ api …
```

- **HTTPS** для `csharp.call-yansburg.com` делает Caddy мессенджера (DNS-запись `A csharp` →
  `185.17.1.109` в Cloudflare, без прокси Cloudflare). Наш `web` портов наружу не открывает
  (`docker-compose.edge.yml`), слушает HTTP внутри сервера.
- **Память** — главное ограничение сервера: лимиты в `docker-compose.prod.yml` дают стеку не больше
  ~1.1 ГБ (обычно ~0.6 ГБ): api 384 МБ (2 воркера), MariaDB 256 МБ, очередь и планировщик по 128 МБ,
  Caddy 128 МБ, Redis 64 МБ. Когда учеников станет много — больше памяти у хостинга или свой сервер.
- **Образы на сервере не собираются**: сборке runner нужно 2+ ГБ памяти — это уронило бы
  мессенджер. Их собирает и проверяет GitHub Actions (workflow `deploy`) после мёржа в `main` и
  кладёт в GHCR: `ghcr.io/nikolas-bars/csharp-school-api` и `-web`, теги — коммит и `latest`.
- **Автодеплоя нет**: обновление — вручную (ниже).
- **HTTP/3 для csharp.call-yansburg.com выключен** — в блоке Caddy мессенджера `header Alt-Svc clear`
  (`clear`, а не просто убрать заголовок: браузеры, уже запомнившие HTTP/3, сразу его забывают). По UDP
  до этого сервера большие файлы идут в разы медленнее: runner (6.6 МБ) по HTTP/3 не загружался и за
  3 минуты, по HTTP/2 — 4–20 с (замеры 2026-10-20). Мессенджер и Inkroom HTTP/3 используют как раньше.
- **Буферы UDP на сервере** подняты до 7.5 МБ (`/etc/sysctl.d/90-quic-buffers.conf`:
  `net.core.rmem_max`, `net.core.wmem_max`) — иначе HTTP/3 у Caddy мессенджера упирался в 208 КБ
  (предупреждение quic-go в его журнале). При переезде на новый сервер — повторить.
- **Деплой мессенджера** (`/opt/starter/starter-backend/deploy/deploy.sh`) пересоздаёт его контейнеры
  только при изменении их образа; правка его `Caddyfile` применяется `caddy reload` без остановки
  (его `deploy/README.md`). Поэтому поменять блок C# School в Caddyfile мессенджера — обычный PR туда.

### Первый запуск

1. **DNS:** в Cloudflare запись `A csharp` → `185.17.1.109`, прокси выключен (серое облако).
2. **Caddy мессенджера** (`starter-backend/deploy/Caddyfile`, рядом с блоком Inkroom) — и
   после мёржа там перезагрузить его конфиг без остановки мессенджера:
   `cd /opt/starter/starter-backend/deploy && git pull && docker compose exec web caddy reload --config /etc/caddy/Caddyfile`:

   ```
   # C# School живёт на этом сервере своим docker compose в /opt/csharp-school (его deploy/README.md).
   # Сертификат получает этот Caddy, запросы уходят в Caddy C# School по сети edge. Не запущен — 502,
   # мессенджер это не задевает
   csharp.{$DOMAIN} {
   	# Без HTTP/3: по UDP до этого сервера большие файлы идут в разы медленнее (ниже)
   	header Alt-Svc clear
   	reverse_proxy csharp-school-web:80
   }
   ```

3. **Образы публичные:** после первого мёржа в `main` — GitHub → профиль → Packages →
   `csharp-school-api` и `csharp-school-web` → Package settings → Change visibility → Public
   (код открыт под MIT, секретов в образах нет). Иначе на сервере нужен `docker login ghcr.io`
   с токеном `read:packages`.
4. **Сервер:**

   ```bash
   ssh starter
   git clone https://github.com/Nikolas-Bars/csharp-school.git /opt/csharp-school
   cd /opt/csharp-school/deploy
   cp .env.production.example .env && chmod 600 .env
   ```

   В `.env`:

   ```bash
   COMPOSE_FILE=docker-compose.prod.yml:docker-compose.edge.yml
   SITE_ADDRESS=:80
   ACME_EMAIL=unused@localhost            # при :80 не используется
   CADDY_TRUSTED_PROXIES=private_ranges   # Caddy мессенджера — в частной сети edge
   IMAGE_PREFIX=ghcr.io/nikolas-bars/csharp-school
   IMAGE_TAG=latest
   APP_URL=https://csharp.call-yansburg.com
   FRONTEND_URL=https://csharp.call-yansburg.com
   RR_NUM_WORKERS=2
   # APP_KEY, DB_PASSWORD, REDIS_PASSWORD — сгенерировать (комментарии в образце);
   # MAIL_* — почтовый сервис, пока его нет — MAIL_MAILER=log (письма только в журнал)
   ```

   Запуск: `./deploy.sh`.

### Обновление, откат, остановка

```bash
ssh starter /opt/csharp-school/deploy/deploy.sh           # latest — последний main
ssh starter /opt/csharp-school/deploy/deploy.sh <sha>     # конкретный коммит main; откат — прошлый
```

Остановить C# School, не трогая мессенджер и Inkroom: `cd /opt/csharp-school/deploy && docker
compose down` (данные остаются в томах).

Сеть `edge` создаёт compose мессенджера: без неё `docker compose up` здесь не запустится.

Переезд на свой сервер: убрать `docker-compose.edge.yml` из `COMPOSE_FILE`, `SITE_ADDRESS` — домен,
`ACME_EMAIL` — настоящая почта, `CADDY_TRUSTED_PROXIES=127.0.0.1/32`; лимиты памяти и
`RR_NUM_WORKERS` — по серверу; база — дампом (раздел «Резервные копии»).

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

Обычный путь — GHCR: workflow `deploy` после мёржа в `main` публикует проверенные образы,
на сервере `IMAGE_PREFIX=ghcr.io/nikolas-bars/csharp-school` и `deploy.sh`. Без реестра:
`make prod-build` на любой машине (x86-64 — как сервер), затем `docker save csharp-school-api:<тег>
csharp-school-web:<тег> | ssh server docker load`.

## Проверка на своей машине

```bash
make prod-check
```

`deploy/check.sh` собирает образы с тегом `check`, поднимает стек отдельным проектом
`csharp-school-prod-check` на http://localhost:15390 (без HTTPS), выполняет миграции и
`course:import`, запускает `deploy/smoke.sh`, а затем удаляет контейнеры, тома и образы. Стек
разработки (`make up`) не затрагивается. То же выполняет CI (`.github/workflows/deploy.yml`).
