<?php

declare(strict_types=1);

namespace common\components;

use Yii;
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
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 900;

    public static function isLocked(string $scope, string $username): bool
    {
        return (int) Yii::$app->cache->get(self::key($scope, $username)) >= self::MAX_FAILURES;
    }

    public static function fail(string $scope, string $username): void
    {
        $key = self::key($scope, $username);
        Yii::$app->cache->set($key, (int) Yii::$app->cache->get($key) + 1, self::LOCK_SECONDS);
    }

    public static function clear(string $scope, string $username): void
    {
        Yii::$app->cache->delete(self::key($scope, $username));
    }

    private static function key(string $scope, string $username): string
    {
        $request = Yii::$app->request;
        $ip = $request instanceof Request ? (string) $request->userIP : '';

        return 'login-throttle:' . $scope . ':' . mb_strtolower(trim($username)) . ':' . $ip;
    }
}
