<?php

use yii\db\Migration;

class m261001_170511_create_coupon_redeem_track_table extends Migration
{

    private $tableName = '{{%coupon_redeem_track}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'redeem_id' => $this->primaryKey(),
                'coupon_id' => $this->integer(11)->notNull()->defaultValue(0),
                'user_id' => $this->integer(11)->notNull()->defaultValue(0),
                'redeem_date' => $this->dateTime()->notNull()->defaultValue('0001-01-01 00:00:00'),
                'redeem_ip' => $this->string(32)->notNull()->defaultValue(''),
                'order_id' => $this->integer(11)->notNull()->defaultValue(0),
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
