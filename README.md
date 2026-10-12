# local_zoo_for_vyatgu

Веб-приложение на чистом PHP 8.5 (без фреймворка) с Eloquent, мигратором и
логированием. Крутится в Docker Compose.

## Стек

| Компонент | Технология |
|---|---|
| Веб-сервер | nginx 1.30 |
| Приложение | PHP 8.5-FPM, Eloquent (illuminate/database), Monolog |
| База данных | PostgreSQL 18 |

Все три образа собираются локально из Dockerfile в `docker/`.

## Быстрый старт

```bash
make init       # создать .env из .env.example
make up         # собрать (если нужно) и запустить контейнеры
make composer   # установить PHP-зависимости
make migrate    # применить миграции схемы
```

Приложение: http://127.0.0.1:8080/
Health-check: http://127.0.0.1:8080/health.php

## Команды

| Команда | Что делает |
|---|---|
| `make init` | создаёт `.env` из `.env.example` |
| `make build` | пересобрать образы |
| `make up` / `make down` | запустить / остановить контейнеры |
| `make ps` | статус контейнеров |
| `make logs` | логи всех сервисов (follow) |
| `make composer` | `composer install` в php-контейнере |
| `make migrate` | применить миграции БД |
| `make psql` | консоль `psql` к PostgreSQL |
| `make sh` | shell в php-контейнере |
| `make clean` | остановить и удалить volume с данными БД |

Полный список — `make help`.

## Конфигурация

Настройки в `.env` (создаётся из `.env.example`):

| Переменная | Назначение | По умолчанию |
|---|---|---|
| `NGINX_PORT` | порт приложения на хосте | `8080` |
| `POSTGRES_PORT` | порт PostgreSQL на хосте | `5432`* |
| `POSTGRES_DB` / `POSTGRES_USER` / `POSTGRES_PASSWORD` | доступы к БД | `app` / `app` / `app_dev_password` |
| `UID` / `GID` | владелец файлов в контейнере | текущий пользователь |

\* Если порт занят локальным PostgreSQL — измените `POSTGRES_PORT` (в этом
проекте используется `5433`).

После правки `.env` перезапустите контейнеры: `make down && make up`.

Приложение читает доступы к БД из переменных окружения `DB_HOST`, `DB_PORT`,
`DB_NAME`, `DB_USER`, `DB_PASSWORD` — их пробрасывает `compose.yaml`.

## Приложение

**Eloquent** подключён через `Capsule\Manager` (`app/src/Bootstrap.php`) —
поэтому фасады Laravel не используются, вместо них — статические вызовы
`Capsule::schema()` / модель `App\Models\Demo`.

**Миграции** лежат в `app/database/migrations/`, запускаются скриптом
`app/database/migrate.php` (аналог `artisan migrate`). История хранится в
таблице `migrations`. Новая миграция — файл `YYYY_MM_DD_HHMMSS_описание.php`,
возвращающий анонимный класс `Migration` с методами `up()` / `down()`.

**Логирование** (Monolog) пишет в `app/storage/logs/app.log` — файл виден на
хосте. Каждый HTTP-запрос логируется записью `http_request` с методом, путём,
телом запроса, кодом и телом ответа. Смотреть в реальном времени:

```bash
docker compose exec php tail -f /var/www/html/storage/logs/app.log
```

## Структура

```
app/public/         веб-корень (index.php, health.php)
app/src/            код приложения (PSR-4: App\ → src/)
  Bootstrap.php     инициализация Eloquent через Capsule
  Logger.php        фабрика логгера Monolog
  Models/Demo.php   модель для таблицы demo
app/database/
  migrate.php       CLI-скрипт запуска миграций
  migrations/       файлы миграций схемы
app/storage/logs/   сюда пишется app.log (в .gitignore)
docker/nginx/       Dockerfile + конфиг nginx
docker/php/         Dockerfile + php.ini + пул php-fpm
docker/postgres/    Dockerfile + postgresql.conf + init-скрипты
compose.yaml        оркестрация сервисов
```

## Примечания

- Правки кода в `app/` видны сразу (F5) — код смонтирован как volume.
- Правки конфигов в `docker/` требуют `docker compose restart <сервис>`.
- `docker/postgres/initdb/*.sql` выполняется **один раз** при инициализации
  пустого тома. Чтобы применить изменения — `make clean && make up` (данные
  БД будут удалены).
