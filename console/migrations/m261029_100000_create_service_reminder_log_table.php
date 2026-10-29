<?php

use yii\db\Migration;

final class m261029_100000_create_service_reminder_log_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%service_reminder_log}}',
                [
                    'id' => $this->bigPrimaryKey(),
                    'service_type' => $this->string(50)->notNull(),
                    'service_id' => $this->integer()->notNull(),
                    'user_id' => $this->integer()->notNull(),
                    'reminder_key' => $this->string(100)->notNull(),
                    'days_before' => $this->integer()->notNull(),
                    'sent_at' => $this->integer()->notNull(),
                    'created_at' => $this->integer()->notNull(),
                ]
        );

        $this->createIndex(
                'idx_service_reminder_unique',
                '{{%service_reminder_log}}',
                [
                    'service_type',
                    'service_id',
                    'reminder_key',
                ],
                true
        );
        $this->createIndex(
                'idx_service_reminder_log_user_id',
                '{{%service_reminder_log}}',
                'user_id'
        );

        $this->addForeignKey(
                'fk_service_reminder_log_user',
                '{{%service_reminder_log}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey(
                'fk_service_reminder_log_user',
                '{{%service_reminder_log}}'
        );

        $this->dropTable(
                '{{%service_reminder_log}}'
        );
    }
}
