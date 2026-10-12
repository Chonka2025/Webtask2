<?php

declare(strict_types=1);

namespace App\Config;

use App\Crypto\Encrypter;
use RuntimeException;

/**
 * Статический конфиг приложения.
 *
 * Активное окружение (dev/prod/...) задаётся переменной окружения APP_ENV.
 * Параметры лежат в файлах config/{env}.php — по одной папке с файлами на
 * окружение, по аналогии с тем, как мигратор сканирует database/migrations:
 * загрузчик берёт файл, чьё имя совпадает с активным окружением.
 *
 * Значения с префиксом "enc:" хранятся зашифрованными прямо в файле конфига
 * и расшифровываются на лету ключом APP_CIPHER_KEY (см. Crypto\Encrypter).
 * Так секреты не лежат в git открытым текстом.
 */
final class Config
{
    private const ENC_PREFIX = 'enc:';

    /** @var array<string, mixed> */
    private static array $params = [];

    private static bool $loaded = false;

    /** Активное окружение (APP_ENV, по умолчанию dev). */
    public static function env(): string
    {
        return getenv('APP_ENV') ?: 'dev';
    }

    /**
     * Значение параметра по точечному пути: Config::get('db.host').
     * Если пути нет — возвращает $default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $value = self::$params;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** Все параметры активного окружения (уже расшифрованные). */
    public static function all(): array
    {
        self::load();

        return self::$params;
    }

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $env = self::env();
        $file = dirname(__DIR__, 2) . '/config/' . $env . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("Нет файла конфига для окружения '{$env}': {$file}");
        }

        /** @var array<string, mixed> $params */
        $params = require $file;

        self::$params = self::decryptValues($params);
        self::$loaded = true;
    }

    /** Рекурсивно расшифровывает строки с префиксом "enc:". */
    private static function decryptValues(array $values): array
    {
        $key = getenv('APP_CIPHER_KEY');
        $encrypter = ($key !== false && $key !== '') ? new Encrypter($key) : null;

        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $values[$name] = self::decryptValues($value);
            } elseif (is_string($value) && str_starts_with($value, self::ENC_PREFIX)) {
                if ($encrypter === null) {
                    throw new RuntimeException("Параметр '{$name}' зашифрован, но APP_CIPHER_KEY не задан");
                }
                $values[$name] = $encrypter->decrypt(substr($value, strlen(self::ENC_PREFIX)));
            }
        }

        return $values;
    }
}
