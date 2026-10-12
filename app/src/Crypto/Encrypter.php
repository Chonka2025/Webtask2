<?php

declare(strict_types=1);

namespace App\Crypto;

use RuntimeException;

/**
 * Шифрование и дешифрование значений конфига через расширение openssl.
 *
 * Алгоритм — AES-256-CBC. Для каждой шифрации генерируется случайный IV,
 * который складывается вместе с шифртекстом в одну base64-строку (поэтому
 * одно и то же значение при повторном шифровании даёт разный результат —
 * это нормально и безопаснее, чем фиксированный IV).
 *
 * Ключ (ровно 32 байта) приходит из переменной окружения APP_CIPHER_KEY.
 */
final class Encrypter
{
    private const CIPHER = 'aes-256-cbc';
    private const KEY_BYTES = 32;

    private readonly string $key;

    public function __construct(string $key)
    {
        // aes-256-cbc требует ключ строго 32 байта. Удобный способ сгенерировать:
        //   php -r "echo bin2hex(random_bytes(16));"   → 32 символа = 32 байта
        if (strlen($key) !== self::KEY_BYTES) {
            throw new RuntimeException(sprintf(
                'Ключ шифрования должен быть ровно %d байт, получено %d. Сгенерируйте: php -r "echo bin2hex(random_bytes(16));"',
                self::KEY_BYTES,
                strlen($key),
            ));
        }

        $this->key = $key;
    }

    /** Шифрует открытый текст → base64-строка (iv + шифртекст). */
    public function encrypt(string $plain): string
    {
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLen);

        $cipher = openssl_encrypt($plain, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('openssl_encrypt: ' . (openssl_error_string() ?: 'неизвестная ошибка'));
        }

        return base64_encode($iv . $cipher);
    }

    /** Дешифрует base64-строку (iv + шифртекст) → открытый текст. */
    public function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false) {
            throw new RuntimeException('Зашифрованное значение не является корректным base64');
        }

        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($raw) <= $ivLen) {
            throw new RuntimeException('Зашифрованное значение слишком коротко (нет IV)');
        }

        $iv = substr($raw, 0, $ivLen);
        $cipher = substr($raw, $ivLen);

        $plain = openssl_decrypt($cipher, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new RuntimeException('openssl_decrypt: ' . (openssl_error_string() ?: 'неизвестная ошибка'));
        }

        return $plain;
    }
}
