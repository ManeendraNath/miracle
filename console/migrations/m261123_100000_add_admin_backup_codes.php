<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * admin.totp_backup_codes: hashes of the one-time codes that stand in for the authenticator app (JSON list).
 * Can be run again after a failure partway.
 */
final class m261123_100000_add_admin_backup_codes extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%admin}}', true)->getColumn('totp_backup_codes') === null) {
            $this->addColumn('{{%admin}}', 'totp_backup_codes', $this->text()->null()->after('totp_last_step'));
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%admin}}', true)->getColumn('totp_backup_codes') !== null) {
            $this->dropColumn('{{%admin}}', 'totp_backup_codes');
        }
    }
}
