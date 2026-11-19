<?php

declare(strict_types=1);

namespace common\components;

use Yii;

/** A fixed-window counter in the cache: the window runs from the first hit, later hits don't restart it. */
final class RateLimit
{
    /** Counts one hit on $key and says whether that makes more than $max in the window. */
    public static function hit(string $key, int $max, int $seconds = 3600): bool
    {
        $window = Yii::$app->cache->get($key);
        [$count, $since] = is_array($window) ? $window : [0, time()];
        Yii::$app->cache->set($key, [$count + 1, $since], max(1, $since + $seconds - time()));

        return $count + 1 > $max;
    }

    /** Whether $key has had its $max hits in this window (the next one would be over), without counting one. */
    public static function over(string $key, int $max): bool
    {
        $window = Yii::$app->cache->get($key);

        return is_array($window) && $window[0] >= $max;
    }
}
