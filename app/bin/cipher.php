<?php

declare(strict_types=1);

/**
 * Консольная утилита шифрования/дешифрования значений конфига.
 *
 * Использование (внутри php-контейнера):
 *
 *   php bin/cipher.php encrypt "открытый-текст"   → base64 (вставь в config как enc:...)
 *   php bin/cipher.php decrypt "base64..."        → открытый текст
 *
 * Ключ берётся из переменной окружения APP_CIPHER_KEY.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Crypto\Encrypter;

$command = $argv[1] ?? '';
$value   = $argv[2] ?? null;

$key = getenv('APP_CIPHER_KEY');
if ($key === false || $key === '') {
    fwrite(STDERR, "Ошибка: не задана переменная окружения APP_CIPHER_KEY\n");
    exit(1);
}

if ($value === null) {
    fwrite(STDERR, "Использование: php bin/cipher.php encrypt|decrypt \"значение\"\n");
    exit(1);
}

try {
    $encrypter = new Encrypter($key);

    match ($command) {
        'encrypt' => print rtrim($encrypter->encrypt($value)) . PHP_EOL,
        'decrypt' => print rtrim($encrypter->decrypt($value)) . PHP_EOL,
        default   => throw new RuntimeException("Неизвестная команда '{$command}'. Используйте: encrypt|decrypt"),
    };
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
