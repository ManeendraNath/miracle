<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use common\components\LoginThrottle;
use Yii;

/** Wrong authenticator codes lock the account after 20 from any address; passwords keep the per-address lock. */
final class LoginThrottleTest extends Unit
{
    public function testGuessesSpreadOverManyAddressesStillLockTheAccount(): void
    {
        $name = 'spread-' . bin2hex(random_bytes(4));
        for ($i = 0; $i < 20; $i++) {
            LoginThrottle::fail(LoginThrottle::SCOPE_ADMIN_CODE, $name);
            LoginThrottle::fail('backend', $name);
            // The 15-minute locks lapse in between: only the account-wide count builds up.
            Yii::$app->cache->delete('login-throttle:' . LoginThrottle::SCOPE_ADMIN_CODE . ':' . $name . ':');
            Yii::$app->cache->delete('login-throttle:backend:' . $name . ':');
        }
        $this->assertTrue(LoginThrottle::isLocked(LoginThrottle::SCOPE_ADMIN_CODE, $name));
        $this->assertFalse(LoginThrottle::isLocked('backend', $name), 'nobody can keep a staff member locked out by guessing passwords');

        LoginThrottle::clear(LoginThrottle::SCOPE_ADMIN_CODE, $name);
        $this->assertFalse(LoginThrottle::isLocked(LoginThrottle::SCOPE_ADMIN_CODE, $name), 'a right code clears it');
    }
}
