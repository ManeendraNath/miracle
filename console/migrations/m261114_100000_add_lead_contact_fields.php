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
        $this->addColumn('{{%lead}}', 'email', $this->string(255)->null()->after('phone_number'));
        $this->addColumn('{{%lead}}', 'subject', $this->string(255)->null()->after('source'));
        $this->addColumn('{{%lead}}', 'message', $this->text()->null()->after('subject'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%lead}}', 'message');
        $this->dropColumn('{{%lead}}', 'subject');
        $this->dropColumn('{{%lead}}', 'email');
    }
}
