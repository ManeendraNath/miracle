<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Verification tokens left on accounts that are already past verification: with one, a customer later set
 * Inactive by staff could reactivate themselves. Data only; nothing to restore on the way down.
 */
final class m261113_100000_clear_stale_verification_tokens extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%user}}', ['verification_token' => null], ['and', ['<>', 'status', \common\models\User::getStatusInactive()], ['not', ['verification_token' => null]]]);
    }

    public function safeDown(): void
    {
    }
}
