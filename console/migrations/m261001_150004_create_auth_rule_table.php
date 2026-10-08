<?php

use yii\db\Migration;


class m261001_150004_create_auth_rule_table extends Migration
{

    private $tableName = '{{%auth_rule}}';

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
                'name' => $this->string(64)->notNull(),
                'data' => $this->text()->defaultValue(null),
                'created_at' => $this->integer(11)->defaultValue(null),
                'updated_at' => $this->integer(11)->defaultValue(null),
            ]);
            
            $this->addPrimaryKey('pk-auth_rule-name', $this->tableName, 'name');
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
