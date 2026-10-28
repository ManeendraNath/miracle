<?php

use yii\db\Migration;

final class m261028_100000_create_admin_notification_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable('admin_notification', [
            'id' => $this->bigPrimaryKey(),
            'type' => $this->string(50)->notNull(),
            'title' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            'route' => $this->string(500),
            'is_read' => $this->boolean()
                    ->notNull()
                    ->defaultValue(false),
            'read_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
                'idx_admin_notification_read',
                'admin_notification',
                'is_read'
        );

        $this->createIndex(
                'idx_admin_notification_created',
                'admin_notification',
                'created_at'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('admin_notification');
    }
}
