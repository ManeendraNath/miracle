<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * admin.totp_secret now holds the encrypted secret (about 160 characters).
 */
final class m261105_100000_widen_admin_totp_secret extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn('{{%admin}}', 'totp_secret', $this->string(255)->null());
    }

    public function safeDown(): void
    {
        $this->alterColumn('{{%admin}}', 'totp_secret', $this->string(64)->null());
    }
}
