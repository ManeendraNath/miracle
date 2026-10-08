<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%audit_request}}`.
 */
class m260917_101019_create_audit_request_table extends Migration
{

    private $tableName = '{{%audit_request}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if the table does not exist
        if ($this->db->schema->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'email' => $this->string(255)->notNull(),
                'phone' => $this->string(50)->defaultValue(null)->comment('Unified from legacy customer_queries'),
                'company_url' => $this->string(255)->defaultValue(null),
                'current_framework' => $this->string(255)->defaultValue(null)->comment('Nullable to support general contact messages'),
                'hosting_environment' => $this->string(255)->defaultValue(null)->comment('Nullable to support general contact messages'),
                'message' => $this->text()->notNull(),
                'status' => "ENUM('Pending', 'In_Review', 'Contacted', 'Closed') NOT NULL DEFAULT 'Pending'",
                'user_ip' => $this->string(50)->defaultValue(null),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ]);

            $this->createIndex('idx-audit_request-status', '{{%audit_request}}', 'status');

        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
