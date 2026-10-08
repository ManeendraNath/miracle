<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%domains}}`.
 */
class m220515_155701_create_domains_table extends Migration
{

    private $tableName = '{{%domains}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer(11)->notNull(),
                'domain_url' => $this->string(255)->notNull()->unique(),
                'registrar' => $this->string(100)->notNull(),
                'registered_date' => $this->date()->notNull(),
                'current_expiry_date' => $this->date()->notNull()->comment('Automatically extended when a renewal history item is added'),
                'status' => $this->smallInteger(2)->notNull()->defaultValue(1)->comment('0: Inactive, 1: Active, 2: Expired'),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ], $tableOptions);
            
            $this->createIndex('idx-domains-user_id', '{{%domains}}', 'user_id');
            $this->createIndex('idx-domains-status', '{{%domains}}', 'status');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
