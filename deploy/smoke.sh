#!/usr/bin/env bash
# Проверка работающего стека снаружи: API, SPA, runner, кэш, сжатие, заголовки безопасности.
# Запуск: deploy/smoke.sh https://csharp.example.org   (после деплоя или из deploy/check.sh)
set -euo pipefail

BASE=${1:?адрес сайта, например https://csharp.example.org}
BASE=${BASE%/}
failures=0

pass() { printf '  ok    %s\n' "$1"; }
fail() { printf '  FAIL  %s\n' "$1"; failures=$((failures + 1)); }

# Заголовки ответа (GET, тело отбрасывается): headers <путь> [Accept-Encoding]
headers() {
    curl -sS -o /dev/null -D - --max-time 30 -H "Accept-Encoding: ${2:-identity}" "$BASE$1" | tr -d '\r'
}

status_of() { printf '%s\n' "$1" | head -n 1 | awk '{print $2}'; }
header_of() { printf '%s\n' "$1" | grep -i "^$2:" | head -n 1 | cut -d' ' -f2- || true; }

expect() { # expect <описание> <фактическое> <ожидаемое — подстрока>
    if [[ "$2" == *"$3"* ]]; then pass "$1: ${2:0:70}"; else fail "$1: «$2», ожидалось «$3»"; fi
}

echo "Проверка $BASE"

h=$(headers /up)
expect "GET /up" "$(status_of "$h")" 200

body=$(curl -sS --max-time 30 -H 'Accept: application/json' "$BASE/api/courses")
expect "GET /api/courses (главная)" "$(printf '%s' "$body" | head -c 60)" '"status":"success"'
expect "  курс C# в списке" "$body" '"slug":"csharp"'
expect "  курс английского в списке" "$body" '"slug":"english"'

body=$(curl -sS --max-time 30 -H 'Accept: application/json' "$BASE/api/courses/csharp")
expect "GET /api/courses/csharp" "$(printf '%s' "$body" | head -c 60)" '"status":"success"'

body=$(curl -sS --max-time 30 -H 'Accept: application/json' "$BASE/api/courses/english")
expect "GET /api/courses/english" "$(printf '%s' "$body" | head -c 60)" '"status":"success"'

body=$(curl -sS --max-time 30 -H 'Accept: application/json' "$BASE/api/courses/english/words")
expect "GET /api/courses/english/words" "$(printf '%s' "$body" | head -c 60)" '"status":"success"'
expect "  карточки слов курса загружены" "$body" '"ipa":'

h=$(headers /api/nope)
expect "GET /api/nope — 404 от API" "$(status_of "$h")" 404
expect "  JSON" "$(header_of "$h" content-type)" application/json

h=$(headers / br)
expect "GET / (index.html)" "$(status_of "$h")" 200
expect "  Content-Type" "$(header_of "$h" content-type)" text/html
expect "  Content-Encoding" "$(header_of "$h" content-encoding)" br
expect "  Cache-Control" "$(header_of "$h" cache-control)" no-cache
csp=$(header_of "$h" content-security-policy)
expect "  CSP: WebAssembly" "$csp" "'wasm-unsafe-eval'"
expect "  CSP: встроенный скрипт по хешу" "$csp" "'sha256-"
expect "  X-Content-Type-Options" "$(header_of "$h" x-content-type-options)" nosniff
expect "  X-Frame-Options" "$(header_of "$h" x-frame-options)" DENY
expect "  HSTS" "$(header_of "$h" strict-transport-security)" max-age=
if [[ -z "$(header_of "$h" server)" ]]; then pass "  Server скрыт"; else fail "  Server: $(header_of "$h" server)"; fi

h=$(headers /courses/csharp/01-first-programs br)
expect "GET маршрута SPA → index.html" "$(status_of "$h")" 200
expect "  Content-Type" "$(header_of "$h" content-type)" text/html

index=$(curl -sS --max-time 30 "$BASE/")
asset=$(printf '%s' "$index" | grep -o '/assets/[^"]*\.js' | head -n 1)
h=$(headers "$asset" br)
expect "GET $asset" "$(status_of "$h")" 200
expect "  Content-Encoding" "$(header_of "$h" content-encoding)" br
expect "  Cache-Control" "$(header_of "$h" cache-control)" immutable

h=$(headers /runner/_framework/dotnet.js br)
expect "GET runner dotnet.js" "$(status_of "$h")" 200
expect "  Content-Type" "$(header_of "$h" content-type)" javascript
expect "  Cache-Control (без отпечатка)" "$(header_of "$h" cache-control)" no-cache

# Файлы runner с отпечатком перечислены в dotnet.js (в нём конфигурация загрузки)
wasm=$(curl -sS --max-time 30 "$BASE/runner/_framework/dotnet.js" | grep -o 'System\.Private\.CoreLib\.[a-z0-9]*\.wasm' | head -n 1)
h=$(headers "/runner/_framework/$wasm" 'br, gzip')
expect "GET runner $wasm" "$(status_of "$h")" 200
expect "  Content-Type" "$(header_of "$h" content-type)" application/wasm
expect "  Content-Encoding" "$(header_of "$h" content-encoding)" br
expect "  Cache-Control" "$(header_of "$h" cache-control)" immutable
expect "  Vary" "$(header_of "$h" vary)" Accept-Encoding

h=$(headers "/runner/_framework/$wasm" gzip)
expect "  только gzip → .gz" "$(header_of "$h" content-encoding)" gzip

h=$(headers /runner/_framework/missing.abcdefghij.wasm br)
expect "GET несуществующего файла runner — 404, не index.html" "$(status_of "$h")" 404

if ((failures > 0)); then
    echo "Ошибок: $failures"
    exit 1
fi
echo "Всё в порядке."
