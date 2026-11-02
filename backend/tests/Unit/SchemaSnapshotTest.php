<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use console\controllers\SchemaController;
use ReflectionMethod;
use Yii;

/**
 * The test DB is built only by migrations, so it must match console/schema/expected.json.
 * Fails when a migration is added without refreshing the snapshot:
 *   php yii_test migrate && php yii_test schema/snapshot
 */
final class SchemaSnapshotTest extends Unit
{
    public function testSnapshotMatchesMigrations(): void
    {
        $controller = new SchemaController('schema', Yii::$app);
        $actual = (new ReflectionMethod($controller, 'readSchema'))->invoke($controller);
        $expected = json_decode((string) file_get_contents(Yii::getAlias('@console/schema/expected.json')), true);

        $this->assertSame($expected, $actual, 'console/schema/expected.json is stale: run php yii_test schema/snapshot');
    }

    public function testMariaDbDefaultsNormalise(): void
    {
        $normalise = new ReflectionMethod(SchemaController::class, 'normaliseDefault');
        $controller = new SchemaController('schema', Yii::$app);

        $this->assertNull($normalise->invoke($controller, null));
        $this->assertNull($normalise->invoke($controller, 'NULL'));
        $this->assertSame('Pending', $normalise->invoke($controller, "'Pending'"));
        $this->assertSame("it's", $normalise->invoke($controller, "'it''s'"));
        $this->assertSame('CURRENT_TIMESTAMP', $normalise->invoke($controller, 'current_timestamp()'));
        $this->assertSame('0', $normalise->invoke($controller, '0'));
    }
}
