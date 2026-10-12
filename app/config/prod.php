<?php

declare(strict_types=1);

/**
 * Конфиг для окружения prod (APP_ENV=prod).
 *
 * Отличается от dev значениями: debug выключен, свои секреты.
 * Секреты (значения "enc:...") шифруются утилитой bin/cipher.php ключом
 * из переменной окружения APP_CIPHER_KEY.
 */
return [
    'app' => [
        'name'  => 'WebTrello',
        'debug' => false,
    ],

    'db' => [
        'host' => getenv('DB_HOST') ?: 'postgres',
        'name' => getenv('DB_NAME') ?: 'app',
    ],

    'secrets' => [
        'api_key' => 'enc:XkBGMWm1N0TVTdpHFvjfcMGr7zk065+4auCVdR3KJoI52KBZ0ERonzoaXIXNkT+C',
    ],
];
