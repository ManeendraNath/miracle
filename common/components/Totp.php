<?php

declare(strict_types=1);

namespace common\components;

use InvalidArgumentException;

/**
 * Time-based one-time passwords (RFC 6238): HMAC-SHA1, 6 digits, 30-second steps,
 * base32 secrets as authenticator apps expect. Works offline, no external calls.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;

    /** A new random 160-bit secret, base32-encoded (32 characters). */
    public static function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }
        return $secret;
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0f;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * The time step the code belongs to (now, or one step either side for clock drift),
     * or null. Steps at or before $lastStep are refused so a used code can't be replayed.
     */
    public static function verify(string $secret, string $code, ?int $lastStep = null, ?int $time = null): ?int
    {
        $code = str_replace(' ', '', $code);
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }
        $now = intdiv($time ?? time(), self::PERIOD);
        for ($step = $now - 1; $step <= $now + 1; $step++) {
            if (($lastStep === null || $step > $lastStep) && hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }
        return null;
    }

    /** otpauth:// URI for the QR code an authenticator app scans. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account)
            . '?' . http_build_query(['secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => self::PERIOD], '', '&', PHP_QUERY_RFC3986);
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(rtrim(strtoupper($secret), '=')) as $char) {
            $value = strpos(self::ALPHABET, $char);
            if ($value === false) {
                throw new InvalidArgumentException('Invalid base32 secret.');
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }
}
