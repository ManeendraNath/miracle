<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * A WhatsApp number for the website's chat button, apart from the phone numbers. Starts as the main phone number.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261209_100000_site_setting_whatsapp extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('whatsapp') === null) {
            // Digits with the country code, like mobile_1 (e.g. 919876543210).
            $this->addColumn('{{%site_setting}}', 'whatsapp', $this->bigInteger()->null()->after('mobile_2'));
            $this->update('{{%site_setting}}', ['whatsapp' => new \yii\db\Expression('mobile_1')]);
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('whatsapp') !== null) {
            $this->dropColumn('{{%site_setting}}', 'whatsapp');
        }
    }
}
