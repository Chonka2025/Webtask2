<?php

declare(strict_types=1);

/**
 * Конфиг для окружения dev (APP_ENV=dev).
 *
 * Возвращает вложенный массив параметров. Значения с префиксом "enc:"
 * хранятся зашифрованными — сгенерируй их утилитой:
 *
 *   docker compose exec php php bin/cipher.php encrypt "секрет"
 *
 * и вставь результат сюда. Расшифровка произойдёт автоматически при чтении
 * через Config::get() (ключ берётся из переменной окружения APP_CIPHER_KEY).
 */
return [
    'app' => [
        'name'  => 'WebTrello',
        'debug' => true,
    ],

    'db' => [
        'host' => getenv('DB_HOST') ?: 'postgres',
        'name' => getenv('DB_NAME') ?: 'app',
    ],

    // Пример секрета: зашифрованное значение (замените на своё после генерации)
    'secrets' => [
        'api_key' => 'enc:vjZPlw7Y9CxHXIG7Burbtu8wWhhgnWPZqTYTXu7GIsVYrYYn1uxqEwPTZwnjOXhQ',
    ],
];
