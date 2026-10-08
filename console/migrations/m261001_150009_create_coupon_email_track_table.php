<?php

use yii\db\Migration;

class m261001_150009_create_coupon_email_track_table extends Migration
{

    private $tableName = '{{%coupon_email_track}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'unique_id' => $this->primaryKey(),
                'coupon_id' => $this->integer(11)->notNull()->defaultValue(0),
                'customer_id_sent' => $this->integer(11)->notNull()->defaultValue(0),
                'sent_firstname' => $this->string(32)->defaultValue(null),
                'sent_lastname' => $this->string(32)->defaultValue(null),
                'emailed_to' => $this->string(32)->defaultValue(null),
                'date_sent' => $this->dateTime()->notNull(),
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
