<?php

declare(strict_types=1);

/**
 * CLI-запуск миграций. Выполняется внутри php-контейнера:
 *
 *   docker compose exec php php database/migrate.php
 *
 * Аналог artisan migrate, но для standalone-приложения без фреймворка.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use Illuminate\Container\Container;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

$capsule = Bootstrap::db();
$dispatcher = new Dispatcher(new Container());

// Репозиторий хранит, какие миграции уже применены (таблица "migrations")
$repository = new DatabaseMigrationRepository(
    $capsule->getDatabaseManager(),
    'migrations'
);

// В illuminate/database v13 сигнатура Migrator: (repository, resolver, files, dispatcher)
$migrator = new Migrator(
    $repository,
    $capsule->getDatabaseManager(),
    new Filesystem(),
    $dispatcher
);

// Создаёт таблицу migrations, если её ещё нет
if (! $repository->repositoryExists()) {
    $repository->createRepository();
}

$migrator->run(__DIR__ . '/migrations');

$ran = $repository->getRan();

echo "Выполнено миграций: " . count($ran) . PHP_EOL;
foreach ($ran as $migration) {
    echo "  - {$migration}" . PHP_EOL;
}
