<?php

use yii\db\Migration;

class m261001_150008_create_coupon_table extends Migration
{

    private $tableName = '{{%coupon}}';

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
                'name' => $this->string(32)->notNull(),
                'description' => $this->text()->defaultValue(null),
                'type' => "ENUM('F', 'P') NOT NULL DEFAULT 'F'",
                'code' => $this->string(32)->notNull()->unique(),
                'amount' => $this->decimal(15, 2)->notNull()->defaultValue(0.00),
                'minimum_order' => $this->decimal(15, 2)->notNull()->defaultValue(0.00),
                'start_date' => $this->dateTime()->defaultValue(null),
                'end_date' => $this->dateTime()->defaultValue(null),
                'uses_per_coupon' => $this->smallInteger(6)->notNull()->defaultValue(1),
                'uses_per_user' => $this->smallInteger(6)->notNull()->defaultValue(0),
                'is_active' => "ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
                'created_at' => $this->integer()->defaultValue(null),
                'updated_at' => $this->integer()->defaultValue(null),
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
