<?php

use yii\db\Migration;

final class m261028_120000_create_whatsapp_log_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable('whatsapp_log', [
            'id' => $this->bigPrimaryKey(),
            'mobile' => $this->string(30)->notNull(),
            'template_name' => $this->string(100),
            'message' => $this->text(),
            'request_payload' => $this->text(),
            'response_payload' => $this->text(),
            'status' => $this->string(30)->notNull(),
            'error_message' => $this->text(),
            'event_key' => $this->string(255),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
                'idx_whatsapp_log_created',
                'whatsapp_log',
                'created_at'
        );
        $this->createIndex(
                'idx_whatsapp_event',
                'whatsapp_log',
                'event_key'
        );
        $this->createIndex(
                'idx_whatsapp_log_status',
                'whatsapp_log',
                'status'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('whatsapp_log');
    }
}
