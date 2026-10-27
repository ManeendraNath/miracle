<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%support_ticket_attachment}}`.
 * Has foreign keys to the tables `{{%support_ticket}}` and `{{%support_ticket_message}}`.
 */
class m261027_110700_create_support_ticket_attachment_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%support_ticket_attachment}}', [
            'id' => $this->bigPrimaryKey(),
            'ticket_id' => $this->integer()->notNull(),
            'ticket_message_id' => $this->bigInteger()->null(),
            'uploaded_by' => $this->integer()->notNull(),
            'file_name' => $this->string(255)->notNull(),
            'file_path' => $this->string(500)->notNull(),
            'file_size' => $this->integer()->notNull(),
            'mime_type' => $this->string(100)->null(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Creates index for column `ticket_id`
        $this->createIndex('{{%idx_ticket}}', '{{%support_ticket_attachment}}', 'ticket_id');

        // Adds foreign key for table `{{%support_ticket}}`
        $this->addForeignKey(
                '{{%fk_sta_ticket}}',
                '{{%support_ticket_attachment}}',
                'ticket_id',
                '{{%support_ticket}}',
                'id',
                'CASCADE'
        );

        // Creates index for column `ticket_message_id`
        $this->createIndex('{{%idx_message}}', '{{%support_ticket_attachment}}', 'ticket_message_id');

        // Adds foreign key for table `{{%support_ticket_message}}`
        $this->addForeignKey(
                '{{%fk_sta_message}}',
                '{{%support_ticket_attachment}}',
                'ticket_message_id',
                '{{%support_ticket_message}}',
                'id',
                'CASCADE'
        );
        
        // Creates index for column `uploaded_by`
        $this->createIndex('{{%idx_uploaded_by}}', '{{%support_ticket_attachment}}', 'uploaded_by');

        // Adds foreign key for table `{{%user}}`
        $this->addForeignKey(
            '{{%fk_sta_user}}',
            '{{%support_ticket_attachment}}',
            'uploaded_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drops foreign key for table `{{%user}}`
        $this->dropForeignKey('{{%fk_sta_user}}', '{{%support_ticket_attachment}}');
        
        // Drops index for column `uploaded_by`
        $this->dropIndex('{{%idx_uploaded_by}}', '{{%support_ticket_attachment}}');

        // Drops foreign key for table `{{%support_ticket}}`
        $this->dropForeignKey('{{%fk_sta_ticket}}', '{{%support_ticket_attachment}}');

        // Drops index for column `ticket_id`
        $this->dropIndex('{{%idx_ticket}}', '{{%support_ticket_attachment}}');

        // Drops foreign key for table `{{%support_ticket_message}}`
        $this->dropForeignKey('{{%fk_sta_message}}', '{{%support_ticket_attachment}}');

        // Drops index for column `ticket_message_id`
        $this->dropIndex('{{%idx_message}}', '{{%support_ticket_attachment}}');

        $this->dropTable('{{%support_ticket_attachment}}');
    }
}
