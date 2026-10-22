<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261021_160000_create_customer_notification_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%customer_notification}}',
                [
                    'id' =>
                    $this->bigPrimaryKey(),
                    'user_id' =>
                            $this->integer()
                            ->notNull(),
                    'type' =>
                            $this->string(50)
                            ->notNull(),
                    'title' =>
                            $this->string(255)
                            ->notNull(),
                    'message' =>
                            $this->text()
                            ->notNull(),
                    'route' =>
                            $this->string(500)
                            ->null(),
                    'is_read' =>
                            $this->boolean()
                            ->notNull()
                            ->defaultValue(false),
                    'read_at' =>
                            $this->integer()
                            ->null(),
                    'created_at' =>
                            $this->integer()
                            ->notNull(),
                ]
        );

        $this->createIndex(
                'idx-customer_notification-user-read',
                '{{%customer_notification}}',
                [
                    'user_id',
                    'is_read',
                ]
        );

        $this->createIndex(
                'idx-customer_notification-user-created',
                '{{%customer_notification}}',
                [
                    'user_id',
                    'created_at',
                ]
        );

        $this->addForeignKey(
                'fk-customer_notification-user',
                '{{%customer_notification}}',
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
                'fk-customer_notification-user',
                '{{%customer_notification}}'
        );

        $this->dropTable(
                '{{%customer_notification}}'
        );
    }
}
