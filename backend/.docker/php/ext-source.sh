#!/bin/sh
# Исходники PECL-расширений для docker-php-ext-install — сборка образа не падает, когда один
# источник временно недоступен (pecl.php.net отвечал 504 и ломал CI).
#
# Использование (после docker-php-source extract): sh ext-source.sh redis [pcov ...]
# Каждое расширение — закреплённая версия и несколько источников: архив PECL и запасной —
# исходники того же тега на GitHub (файлы совпадают с пакетом PECL, кроме подмодуля liblzf —
# он нужен лишь для сжатия lzf, которое не включаем). Источники пробуются по кругу, до 3 кругов
# с растущей паузой; архив принимается, только если совпала его контрольная сумма SHA-256.
# Распаковка — в /usr/src/php/ext/<имя>, оттуда собирает docker-php-ext-install.
#
# Новая версия: поменять версию в обеих ссылках и обе суммы (sha256sum скачанных архивов;
# сверить, что файлы архива GitHub совпадают с пакетом PECL).
set -eu

# <sha256> <url> — по порядку предпочтения
sources() {
    case $1 in
        redis)
            echo 0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5 \
                https://pecl.php.net/get/redis-6.3.0.tgz
            echo cb8f81df1a275599e4f8ddcfec7e1f65ed1953e6f5673649149fd680ebff4cad \
                https://codeload.github.com/phpredis/phpredis/tar.gz/refs/tags/6.3.0
            ;;
        pcov)
            echo 23255c8c9335a9636ccb743f5302436a97a582a0bbde9869485be911bbc15da8 \
                https://pecl.php.net/get/pcov-1.0.12.tgz
            echo fdd07cad8e2ff42f0c9f095d84aeef11dab0fde7a008805f61883cbcb1b3f12b \
                https://codeload.github.com/krakjoe/pcov/tar.gz/refs/tags/v1.0.12
            ;;
        *)
            echo "ext-source: неизвестное расширение $1" >&2
            return 1
            ;;
    esac
}

fetch() {
    name=$1
    list=$(sources "$name")
    archive=$(mktemp)
    round=1
    while :; do
        set -- $list
        while [ $# -ge 2 ]; do
            sum=$1 url=$2
            shift 2
            if ! curl -fsSL --proto '=https' --tlsv1.2 --connect-timeout 10 --max-time 60 \
                -o "$archive" "$url"; then
                echo "ext-source: $name — $url недоступен (круг $round)" >&2
            elif [ "$(sha256sum "$archive" | cut -d ' ' -f 1)" != "$sum" ]; then
                echo "ext-source: $name — у архива $url не та контрольная сумма, он отброшен" >&2
            else
                dest=/usr/src/php/ext/$name
                rm -rf "$dest" && mkdir -p "$dest"
                # Верхний каталог архива отбрасывается: у PECL — <имя>-<версия>, у GitHub — <репо>-<тег>
                tar -xzf "$archive" -C "$dest" --strip-components=1
                rm -f "$archive"
                echo "ext-source: $name — из $url"
                return 0
            fi
        done
        if [ "$round" -ge 3 ]; then
            rm -f "$archive"
            echo "ext-source: $name — ни один источник не дал архив с верной суммой" >&2
            return 1
        fi
        sleep $((round * 10))
        round=$((round + 1))
    done
}

[ -d /usr/src/php/ext ] || { echo "ext-source: сначала docker-php-source extract" >&2; exit 1; }
for ext in "$@"; do
    fetch "$ext"
done
