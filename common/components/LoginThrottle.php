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
 * Staff accounts also have an account-wide limit (ACCOUNT_LIMITS, any IP): guesses spread over many
 * addresses still stop, and a run of wrong authenticator codes locks the account for a day and alerts admins.
 * ponytail: per-server FileCache counters; switch to a shared cache if the app runs on several hosts.
 */
final class LoginThrottle
{
    /** Authenticator codes on the admin panel. Only someone who has the password can reach them. */
    public const SCOPE_ADMIN_CODE = 'backend-code';
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 900;
    /** scope => [failures from any IP, then locked for this many seconds after the last failure]. */
    private const ACCOUNT_LIMITS = ['backend' => [20, 3600], self::SCOPE_ADMIN_CODE => [20, 86400]];
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
        return (int) Yii::$app->cache->get(self::key($scope, $username)) >= self::MAX_FAILURES
            || (isset(self::ACCOUNT_LIMITS[$scope]) && (int) Yii::$app->cache->get(self::accountKey($scope, $username)) >= self::ACCOUNT_LIMITS[$scope][0]);
    }

    public static function fail(string $scope, string $username): void
    {
        if (isset(self::ACCOUNT_LIMITS[$scope])) {
            [$limit, $seconds] = self::ACCOUNT_LIMITS[$scope];
            if (self::increment(self::accountKey($scope, $username), $seconds) === $limit && $scope === self::SCOPE_ADMIN_CODE) {
                try {
                    \common\services\admin\AdminAlertService::twoFactorChanged($username, 'locked for 24 hours after ' . $limit . ' wrong authenticator codes. Someone may have this password: change it');
                } catch (\Throwable $e) {
                    Yii::error($e, __METHOD__);
                }
            }
        }
        self::increment(self::key($scope, $username), self::LOCK_SECONDS);
    }

    /** @return int the new count */
    private static function increment(string $key, int $seconds): int
    {
        // Lock the read-increment-write so parallel failed logins can't overwrite
        // each other's count and get extra attempts.
        // ponytail: FileMutex is per-server; switch to MysqlMutex if the app runs on several hosts.
        $mutex = new FileMutex();
        $locked = $mutex->acquire($key, 2);
        try {
            $count = (int) Yii::$app->cache->get($key) + 1;
            Yii::$app->cache->set($key, $count, $seconds);
        } finally {
            if ($locked) {
                $mutex->release($key);
            }
        }

        return $count;
    }

    public static function clear(string $scope, string $username): void
    {
        Yii::$app->cache->delete(self::key($scope, $username));
        Yii::$app->cache->delete(self::accountKey($scope, $username));
    }

    private static function accountKey(string $scope, string $username): string
    {
        return 'login-throttle-account:' . $scope . ':' . mb_strtolower(trim($username));
    }

    private static function key(string $scope, string $username): string
    {
        $request = Yii::$app->request;
        // Coupon and authenticator-code guesses count per account: changing network must not start again.
        $ip = $request instanceof Request && !in_array($scope, ['coupon', self::SCOPE_ADMIN_CODE], true) ? (string) $request->userIP : '';

        return 'login-throttle:' . $scope . ':' . mb_strtolower(trim($username)) . ':' . $ip;
    }
}
