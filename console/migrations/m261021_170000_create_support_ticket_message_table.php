<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_170000_create_support_ticket_message_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%support_ticket_message}}',
                [
                    'id' =>
                    $this->bigPrimaryKey(),
                    'ticket_id' =>
                            $this->integer()
                            ->notNull(),
                    'sender_type' =>
                            $this->string(20)
                            ->notNull(),
                    'sender_id' =>
                            $this->integer()
                            ->null(),
                    'message' =>
                            $this->text()
                            ->notNull(),
                    'created_at' =>
                            $this->integer()
                            ->notNull(),
                ]
        );

        $this->createIndex(
                'idx-support_ticket_message-ticket',
                '{{%support_ticket_message}}',
                [
                    'ticket_id',
                    'created_at',
                ]
        );

        $this->addForeignKey(
                'fk-support_ticket_message-ticket',
                '{{%support_ticket_message}}',
                'ticket_id',
                '{{%support_ticket}}',
                'id',
                'CASCADE',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey(
                'fk-support_ticket_message-ticket',
                '{{%support_ticket_message}}'
        );

        $this->dropTable(
                '{{%support_ticket_message}}'
        );
    }
}
