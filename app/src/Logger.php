<?php

declare(strict_types=1);

namespace App;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Фабрика логгера на базе Monolog.
 *
 * Пишем в app/storage/logs/app.log — каталог смонтирован bind mount'ом,
 * поэтому лог виден прямо на хосте без всякого «docker exec».
 */
final class Logger
{
    private static ?MonologLogger $logger = null;

    public static function make(): MonologLogger
    {
        if (self::$logger !== null) {
            return self::$logger;
        }

        $dir = __DIR__ . '/../storage/logs';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handler = new StreamHandler($dir . '/app.log', MonologLogger::DEBUG);

        // %context% — это массив, который мы передаём вторым аргументом в info()/error().
        // allowInlineLineBreaks + ignoreEmptyContextAndExtra дают чистый однострочный JSON.
        $handler->setFormatter(new LineFormatter(
            format: '[%datetime%] %level_name% %message% %context%',
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: false,
            ignoreEmptyContextAndExtra: true,
        ));

        $logger = new MonologLogger('app');
        $logger->pushHandler($handler);

        return self::$logger = $logger;
    }
}
