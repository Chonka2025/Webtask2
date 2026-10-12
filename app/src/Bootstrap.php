<?php

declare(strict_types=1);

namespace App;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

/**
 * Точка инициализации Eloquent для приложения без фреймворка.
 *
 * Capsule — официальный «standalone»-способ подключить illuminate/database:
 * он собирает соединение, поднимает event dispatcher и регистрирует Eloquent,
 * не требуя ServiceProvider'ов и фасадов Laravel.
 */
final class Bootstrap
{
    private static ?Capsule $capsule = null;

    /**
     * Возвращает настроенный и уже «прогретый» Capsule (Singleton).
     *
     * setAsGlobal() важен: он даёт статический доступ Capsule::schema()/Capsule::table()
     * прямо из файлов миграций, где нет ссылки на экземпляр.
     */
    public static function db(): Capsule
    {
        if (self::$capsule !== null) {
            return self::$capsule;
        }

        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'   => 'pgsql',
            'host'     => getenv('DB_HOST') ?: 'postgres',
            'port'     => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_NAME') ?: 'app',
            'username' => getenv('DB_USER') ?: 'app',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ]);

        // Dispatcher ставим ДО bootEloquent: Eloquent подписывается на события
        // (creating/created/retrieved и т.д.) через него при инициализации
        $capsule->setEventDispatcher(new Dispatcher(new Container()));

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return self::$capsule = $capsule;
    }
}
