<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Optional two-factor sign-in for admin-panel accounts (TOTP authenticator app).
 * totp_secret NULL = two-factor off. totp_last_step = time step of the last accepted
 * code, so the same code can't be used twice.
 */
final class m261104_100000_add_totp_to_admin_table extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%admin}}', 'totp_secret', $this->string(64)->null()->after('password_reset_token'));
        $this->addColumn('{{%admin}}', 'totp_last_step', $this->integer()->null()->after('totp_secret'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%admin}}', 'totp_last_step');
        $this->dropColumn('{{%admin}}', 'totp_secret');
    }
}
