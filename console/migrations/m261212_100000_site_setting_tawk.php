<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Live chat on the public pages through Tawk.to: its property and widget ids, as "propertyId/widgetId". Empty: no chat.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261212_100000_site_setting_tawk extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('tawk_chat') === null) {
            $this->addColumn('{{%site_setting}}', 'tawk_chat', $this->string(100)->null()->after('whatsapp'));
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('tawk_chat') !== null) {
            $this->dropColumn('{{%site_setting}}', 'tawk_chat');
        }
    }
}
