<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The contact form saves each enquiry as a lead, so a failed email can't lose it.
 */
final class m261114_100000_add_lead_contact_fields extends Migration
{
    public function safeUp(): void
    {
        // MySQL DDL isn't transactional: each step can be run again after a failure partway.
        $lead = fn(): \yii\db\TableSchema => $this->db->getTableSchema('{{%lead}}', true);
        if ($lead()->getColumn('email') === null) {
            $this->addColumn('{{%lead}}', 'email', $this->string(255)->null()->after('phone_number'));
        }
        if ($lead()->getColumn('subject') === null) {
            $this->addColumn('{{%lead}}', 'subject', $this->string(255)->null()->after('source'));
        }
        if ($lead()->getColumn('message') === null) {
            $this->addColumn('{{%lead}}', 'message', $this->text()->null()->after('subject'));
        }
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%lead}}', 'message');
        $this->dropColumn('{{%lead}}', 'subject');
        $this->dropColumn('{{%lead}}', 'email');
    }
}
