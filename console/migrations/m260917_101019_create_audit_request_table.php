<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%audit_request}}`.
 */
class m260917_101019_create_audit_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%audit_request}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'company_url' => $this->string(),
            'current_framework' => $this->string()->notNull(),
            'hosting_environment' => $this->string()->notNull(),
            'message' => $this->text()->notNull(),
            'status' => $this->string(50)->defaultValue('Pending'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%audit_request}}');
    }
}
