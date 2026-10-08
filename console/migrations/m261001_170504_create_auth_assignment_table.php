<?php

use yii\db\Migration;

class m261001_170504_create_auth_assignment_table extends Migration
{

    private $tableName = '{{%auth_assignment}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'item_name' => $this->string(64)->notNull(),
                'user_id' => $this->integer(11)->notNull(),
                'created_at' => $this->integer(11)->defaultValue(null),
            ]);
            
            $this->addPrimaryKey('pk-auth_assignment', $this->tableName, ['item_name', 'user_id']);
            $this->createIndex('idx-auth_assignment-user_id', $this->tableName, 'user_id');
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}