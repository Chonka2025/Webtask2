# Учебное окружение: nginx + php-fpm + PostgreSQL

Простой стек для практики Docker Compose: nginx как веб-сервер и
реверс-прокси к php-fpm 8.5 (чистый PHP, без фреймворков), PostgreSQL 18
как база данных. Все три образа собираются локально из Dockerfile в `docker/`.

## Быстрый старт

```bash
make init && make up && make composer
```

- `make init` создаёт `.env` из `.env.example` и подставляет в него UID/GID
  текущего пользователя (нужно, чтобы файлы, которые пишет php-контейнер,
  на хосте принадлежали вам, а не root).
- `make up` собирает (если ещё не собраны) и поднимает контейнеры.
- `make composer` ставит зависимости приложения в `app/vendor/`.

После этого:

- приложение — http://127.0.0.1:8080/
- health-check — http://127.0.0.1:8080/health.php
- PostgreSQL слушает `127.0.0.1:5432` — можно подключиться любым GUI-клиентом
  (логин/пароль/база — из `.env`), либо `make psql` для консоли.

Остальные команды — в `make help`.

## Когда пересобирать, а когда достаточно F5 или restart

- **Правки в `app/`** (PHP-код) — видны сразу, без пересборки и рестарта.
  `./app` смонтирован в контейнеры как volume, а opcache в dev-конфиге
  (`opcache.validate_timestamps = 1`, `opcache.revalidate_freq = 0`)
  проверяет изменения файла на каждый запрос. Просто обновите страницу.
- **Правки в конфигах** (`docker/nginx/conf.d/*.conf`, `php.ini`, `www.conf`,
  `postgresql.conf`) — тоже volume, но применяются не мгновенно: нужен
  `docker compose restart <сервис>`, потому что nginx/php-fpm/postgres
  читают их только при старте.
- **Правки в `Dockerfile` или `app/composer.json`** (новое системное
  расширение PHP, системный пакет, PHP-зависимость) — нужен `make build`,
  затем `make up` (и `make composer`, если менялся `composer.json`).

## Ловушка: initdb выполняется только один раз

`docker/postgres/initdb/*.sql` применяется исключительно при инициализации
**пустого** тома `pgdata` — то есть один раз, при самом первом старте. Если
окружение уже поднималось и вы правите `01-init.sql`, он не выполнится
повторно сам по себе. Чтобы применить изменения — снести том и поднять
заново:

```bash
make clean && make up
```

Это удалит все данные в PostgreSQL.

## Linux / macOS / Windows (WSL2)

`make init` подставляет в `.env` реальные UID/GID текущего пользователя, а
Dockerfile php пересоздаёт `www-data` с этими же ID (`groupmod`/`usermod -o`,
флаг `-o` — потому что нужный GID нередко уже занят какой-то системной группой
в образе, например `dialout`). За счёт этого владелец файлов, которые
php-fpm пишет в bind mount (`app/vendor` и т.п.), на хосте совпадает с вами,
а не с root — проверено на Linux, macOS и Windows/WSL2.

- **Linux** — работает «из коробки»: bind mount напрямую транслирует UID/GID
  между хостом и контейнером.
- **macOS** (Docker Desktop) — тоже напрямую: virtiofs корректно пробрасывает
  владельца файлов.
- **Windows + WSL2** — запускайте `make`/`docker compose` из терминала
  WSL2-дистрибутива (не из PowerShell/cmd) и держите проект **внутри
  файловой системы WSL2** (например `~/projects/...`), а не на `/mnt/c/...`.
  Диски Windows подключены в WSL2 через DrvFs, которая не хранит настоящие
  Unix-права — владелец файлов там не будет соответствовать UID/GID из
  `.env`, независимо от настроек Dockerfile. Также проверьте, что в Docker
  Desktop включена WSL-интеграция для вашего дистрибутива (Settings →
  Resources → WSL Integration).
- **Переносы строк** — `.gitattributes` форсирует LF для всех файлов
  репозитория, чтобы `git clone` на Windows с `core.autocrlf=true` не
  проставил CRLF в Dockerfile/Makefile/конфигах (это бы сломало их разбор
  внутри Linux-контейнеров).

## Структура

```
app/            — код приложения (PSR-4 автозагрузка App\ → src/)
docker/nginx/   — Dockerfile + конфиг nginx
docker/php/     — Dockerfile + php.ini + пул php-fpm (www.conf)
docker/postgres/— Dockerfile + postgresql.conf + init-скрипты
compose.yaml    — оркестрация; значения по умолчанию — в .env.example
```
