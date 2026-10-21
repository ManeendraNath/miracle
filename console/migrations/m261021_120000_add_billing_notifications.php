<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_120000_add_billing_notifications extends Migration
{

    public function safeUp(): void
    {
        $this->addColumn('{{%transaction}}', 'confirmation_emailed_at', $this->integer()->null()->after('paid_at'));

        $this->createTable(
                '{{%invoice_reminder_log}}',
                [
                    'id' => $this->bigPrimaryKey(),
                    'invoice_id' => $this->integer()->notNull(),
                    'reminder_key' => $this->string(50)->notNull(),
                    'days_from_due' => $this->integer()->notNull(),
                    'recipient_email' => $this->string(255)->notNull(),
                    'sent_at' => $this->integer()->notNull(),
                    'created_at' => $this->integer()->notNull(),
                ]
        );

        $this->createIndex(
                'uq-invoice_reminder_log-invoice-key',
                '{{%invoice_reminder_log}}',
                [
                    'invoice_id',
                    'reminder_key',
                ],
                true
        );

        $this->createIndex(
                'idx-invoice_reminder_log-sent',
                '{{%invoice_reminder_log}}',
                'sent_at'
        );

        $this->addForeignKey(
                'fk-invoice_reminder_log-invoice',
                '{{%invoice_reminder_log}}',
                'invoice_id',
                '{{%invoice}}',
                'id',
                'CASCADE',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey(
                'fk-invoice_reminder_log-invoice',
                '{{%invoice_reminder_log}}'
        );

        $this->dropTable(
                '{{%invoice_reminder_log}}'
        );

        $this->dropColumn(
                '{{%transaction}}',
                'confirmation_emailed_at'
        );
    }
}
