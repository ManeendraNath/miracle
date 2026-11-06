<?php

declare(strict_types=1);

namespace common\components;

use Yii;
use yii\mutex\FileMutex;
use yii\web\Request;

/**
 * Locks a username+IP pair for LOCK_SECONDS after MAX_FAILURES failed logins.
 * Keyed on the pair (not the username alone) so an attacker can't lock the
 * real user out.
 * ponytail: per-server FileCache counter; doesn't stop distributed attacks
 * across many IPs, add a per-username captcha if that shows up in the logs.
 */
final class LoginThrottle
{
    /** Authenticator codes on the admin panel. Only someone who has the password can reach them. */
    public const SCOPE_ADMIN_CODE = 'backend-code';
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 900;
    /** Hash of a random value nobody knows, same bcrypt cost as real passwords. */
    private const NOBODY_HASH = '$2y$13$uazifTpQx.6hhBxafktGD.8o6XxERVEQNmKS6KUy2I/JCGly/VvfW';

    /** Always false, but takes as long as a real password check: response time must not reveal which usernames exist. */
    public static function checkAgainstNobody(string $password): bool
    {
        password_verify($password, self::NOBODY_HASH);

        return false;
    }

    public static function isLocked(string $scope, string $username): bool
    {
        return (int) Yii::$app->cache->get(self::key($scope, $username)) >= self::MAX_FAILURES;
    }

    public static function fail(string $scope, string $username): void
    {
        $key = self::key($scope, $username);
        // Lock the read-increment-write so parallel failed logins can't overwrite
        // each other's count and get extra attempts.
        // ponytail: FileMutex is per-server; switch to MysqlMutex if the app runs on several hosts.
        $mutex = new FileMutex();
        $locked = $mutex->acquire($key, 2);
        try {
            Yii::$app->cache->set($key, (int) Yii::$app->cache->get($key) + 1, self::LOCK_SECONDS);
        } finally {
            if ($locked) {
                $mutex->release($key);
            }
        }
    }

    public static function clear(string $scope, string $username): void
    {
        Yii::$app->cache->delete(self::key($scope, $username));
    }

    private static function key(string $scope, string $username): string
    {
        $request = Yii::$app->request;
        // Coupon and authenticator-code guesses count per account: changing network must not start again.
        $ip = $request instanceof Request && !in_array($scope, ['coupon', self::SCOPE_ADMIN_CODE], true) ? (string) $request->userIP : '';

        return 'login-throttle:' . $scope . ':' . mb_strtolower(trim($username)) . ':' . $ip;
    }
}
