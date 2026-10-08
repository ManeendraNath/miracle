<?php

use yii\db\Migration;

class m261006_105502_create_invoice_item_table extends Migration
{

    private $tableName = '{{%invoice_item}}';

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'invoice_id' => $this->integer(11)->notNull(),
                'item_type' => $this->tinyInteger()->null()->defaultValue(4)->comment('1 : domain, 2 :hosting, 3 : maintenance, 4 : custom'),
                'item_id' => $this->integer(11)->defaultValue(null)->comment('Maps to the primary key of the domain/hosting/maintenance assets'),
                'category' => $this->string(100)->notNull(),
                'description' => $this->text()->notNull(),
                'quantity' => $this->integer(11)->notNull()->defaultValue(1),
                'unit_price' => $this->decimal(10, 2)->notNull(),
                'total_price' => $this->decimal(10, 2)->notNull(),
            ]);

            $this->createIndex('idx-invoice_item-invoice_id', $this->tableName, 'invoice_id');
            $this->createIndex('idx-invoice_item-lookup', $this->tableName, ['item_type', 'item_id']);
        }
    }

    public function down()
    {
        $this->dropTable($this->tableName);
    }
}
