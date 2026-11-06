<?php

declare(strict_types=1);

namespace common\components;

use Yii;
use yii\db\Connection;

/** Emails don't belong inside a transaction: they hold its locks, and a rollback can't take them back. */
final class AfterCommit
{
    /** Runs $job once the open transaction has committed, never if it rolls back; straight away if there is none. */
    public static function run(callable $job): void
    {
        $db = Yii::$app->db;
        if ($db->getTransaction() === null) {
            $job();

            return;
        }
        $onCommit = $onRollback = null;
        $forget = static function () use ($db, &$onCommit, &$onRollback): void {
            $db->off(Connection::EVENT_COMMIT_TRANSACTION, $onCommit);
            $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, $onRollback);
        };
        $onCommit = static function () use ($forget, $job): void {
            $forget();
            $job();
        };
        $onRollback = static function () use ($forget): void {
            $forget();
        };
        $db->on(Connection::EVENT_COMMIT_TRANSACTION, $onCommit);
        $db->on(Connection::EVENT_ROLLBACK_TRANSACTION, $onRollback);
    }
}
