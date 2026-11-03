<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use Yii;
use yii\db\Connection;

/**
 * Billing code locks a row and then reads it (and its payments / coupon uses). Under MySQL's
 * default REPEATABLE READ that read can return data from before the lock was taken, so the
 * connection is switched to READ COMMITTED (common/config/main.php).
 *
 * Only the setting is checked: the Codeception Yii2 module shares one session between all
 * connections in a test, so two concurrent requests can't be simulated here.
 */
final class DbIsolationTest extends Unit
{
    public function testConnectionsUseReadCommitted(): void
    {
        /** @var Connection $db a fresh connection from the app's db config, incl. its afterOpen handler */
        $db = Yii::createObject(Yii::$app->getComponents()['db']);
        $this->assertSame('READ-COMMITTED', $db->createCommand('SELECT @@transaction_isolation')->queryScalar());
        $db->close();
    }
}
