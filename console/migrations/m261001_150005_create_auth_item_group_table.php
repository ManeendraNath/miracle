<?php

use yii\db\Migration;


class m261001_150005_create_auth_item_group_table extends Migration
{

    private $tableName = '{{%auth_item_group}}';

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
                'code' => $this->string(64)->notNull(),
                'name' => $this->string(255)->notNull(),
                'created_at' => $this->integer(11)->defaultValue(null),
                'updated_at' => $this->integer(11)->defaultValue(null),
            ]);
            
            $this->addPrimaryKey('pk-auth_item_group-code', $this->tableName, 'code');

            // Seed Initial Groups
            $this->batchInsert($this->tableName, ['code', 'name', 'created_at', 'updated_at'], [
                ['userCommonPermissions', 'User common permission', 1652196353, 1652196353],
                ['userManagement', 'User management', 1652196352, 1652196352],
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
