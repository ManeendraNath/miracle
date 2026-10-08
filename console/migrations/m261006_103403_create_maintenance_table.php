<?php

use yii\db\Migration;

class m261006_103403_create_maintenance_table extends Migration
{

    private $tableName = '{{%maintenance}}';

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer(11)->notNull(),
                'website_url' => $this->string(255)->notNull(),
                'start_date' => $this->date()->notNull(),
                'current_expiry_date' => $this->date()->notNull()->comment('Extended automatically when a renewal history ledger record is verified'),
                'status' => $this->smallInteger(2)->notNull()->defaultValue(1)->comment('0: Cancelled, 1: Active, 2: Overdue'),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ]);
            $this->createIndex('idx-maintenance-user_id', '{{%maintenance}}', 'user_id');
            $this->createIndex('idx-maintenance-status', '{{%maintenance}}', 'status');
        }
    }

    public function down()
    {
        $this->dropTable($this->tableName);
    }
}
