<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_161000_create_support_ticket_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%support_ticket}}',
                [
                    'id' =>
                    $this->primaryKey(),
                    'ticket_number' =>
                            $this->string(30)
                            ->notNull(),
                    'user_id' =>
                            $this->integer()
                            ->notNull(),
                    'service_type' =>
                            $this->string(30)
                            ->null(),
                    'service_id' =>
                            $this->integer()
                            ->null(),
                    'subject' =>
                            $this->string(255)
                            ->notNull(),
                    'message' =>
                            $this->text()
                            ->notNull(),
                    'priority' =>
                            $this->string(20)
                            ->notNull()
                            ->defaultValue('Normal'),
                    'status' =>
                            $this->string(30)
                            ->notNull()
                            ->defaultValue('Open'),
                    'admin_response' =>
                            $this->text()
                            ->null(),
                    'closed_at' =>
                            $this->integer()
                            ->null(),
                    'created_at' =>
                            $this->integer()
                            ->notNull(),
                    'updated_at' =>
                            $this->integer()
                            ->notNull(),
                ]
        );

        $this->createIndex(
                'uq-support_ticket-number',
                '{{%support_ticket}}',
                'ticket_number',
                true
        );

        $this->createIndex(
                'idx-support_ticket-user-status',
                '{{%support_ticket}}',
                [
                    'user_id',
                    'status',
                ]
        );

        $this->addForeignKey(
                'fk-support_ticket-user',
                '{{%support_ticket}}',
                'user_id',
                '{{%user}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey(
                'fk-support_ticket-user',
                '{{%support_ticket}}'
        );

        $this->dropTable(
                '{{%support_ticket}}'
        );
    }
}
