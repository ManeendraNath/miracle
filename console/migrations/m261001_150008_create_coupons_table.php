<?php

use yii\db\Migration;

class m261001_150008_create_coupons_table extends Migration
{

    private $tableName = '{{%coupons}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'coupon_id' => $this->primaryKey(),
                'coupon_name' => $this->string(32)->notNull(),
                'coupon_description' => $this->text()->defaultValue(null),
                'coupon_type' => "ENUM('F', 'P') NOT NULL DEFAULT 'F'",
                'coupon_code' => $this->string(32)->notNull(),
                'coupon_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0.00),
                'coupon_minimum_order' => $this->decimal(15, 2)->notNull()->defaultValue(0.00),
                'coupon_start_date' => $this->dateTime()->defaultValue(null),
                'coupon_expire_date' => $this->dateTime()->defaultValue(null),
                'uses_per_coupon' => $this->smallInteger(6)->notNull()->defaultValue(1),
                'uses_per_user' => $this->smallInteger(6)->notNull()->defaultValue(0),
                'coupon_active' => "ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
                'date_created' => $this->dateTime()->defaultValue(null),
            ]);

            $this->createIndex('idx-unique-coupon_code', $this->tableName, 'coupon_code', true);
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
