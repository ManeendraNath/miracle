<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use common\components\LoginThrottle;
use Yii;

/** Staff accounts lock after 20 failures from any address, not only 5 from one. */
final class LoginThrottleTest extends Unit
{
    public function testGuessesSpreadOverManyAddressesStillLockTheAccount(): void
    {
        $name = 'spread-' . bin2hex(random_bytes(4));
        for ($i = 0; $i < 20; $i++) {
            LoginThrottle::fail('backend', $name);
            // A new address each time: the per-address count never builds up.
            Yii::$app->cache->delete('login-throttle:backend:' . $name . ':');
        }
        $this->assertTrue(LoginThrottle::isLocked('backend', $name));

        LoginThrottle::clear('backend', $name);
        $this->assertFalse(LoginThrottle::isLocked('backend', $name), 'the right password clears it');
        $this->assertFalse(LoginThrottle::isLocked('frontend', $name), 'customers keep the per-address lock only');
    }
}
