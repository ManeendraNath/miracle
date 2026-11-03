<?php

declare(strict_types=1);

namespace common\helpers;

use Yii;

/**
 * Encrypts small secrets for storage in the database (admin 2FA secrets, cPanel passwords) with
 * TWO_FACTOR_KEY from .env. Stored form: "enc:" + base64 of Yii's encryptByKey output.
 * Values saved before encryption existed have no prefix and are read back as-is.
 */
final class SecretBox
{
    private const PREFIX = 'enc:';

    private function __construct()
    {
    }

    /** Whether TWO_FACTOR_KEY is set, so secrets can be stored. */
    public static function hasKey(): bool
    {
        return strlen(self::key()) >= 32;
    }

    /** @param string|null $purpose a separate key is derived per purpose; null = the 2FA secrets' original scheme */
    public static function encrypt(string $plain, ?string $purpose = null): string
    {
        if (!self::hasKey()) {
            throw new \RuntimeException('Set TWO_FACTOR_KEY (64 hex characters) in .env first.');
        }

        return self::PREFIX . base64_encode(Yii::$app->security->encryptByKey($plain, self::key(), $purpose));
    }

    /** The plain value; null when it can't be decrypted (key missing or changed). */
    public static function decrypt(string $stored, ?string $purpose = null): ?string
    {
        if (!self::isEncrypted($stored)) {
            return $stored;
        }
        $plain = self::hasKey()
            ? Yii::$app->security->decryptByKey(base64_decode(substr($stored, strlen(self::PREFIX))), self::key(), $purpose)
            : false;

        return is_string($plain) ? $plain : null;
    }

    public static function isEncrypted(?string $stored): bool
    {
        return str_starts_with((string) $stored, self::PREFIX);
    }

    private static function key(): string
    {
        return (string) (Yii::$app->params['twoFactorKey'] ?? '');
    }
}
