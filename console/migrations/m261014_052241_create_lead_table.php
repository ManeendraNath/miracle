<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%lead}}`.
 */
class m261014_052241_create_lead_table extends Migration
{

    private $tableName = '{{%lead}}';

    public function safeUp()
    {
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'lead_name' => $this->string(150)->notNull(),
            'phone_number' => $this->string(30)->null(),
            'source' => $this->string(100)->null(),
            'status' => $this->string(50)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
                'idx-lead-status',
                $this->tableName,
                'status'
        );

        $this->createIndex(
                'idx-lead-created_at',
                $this->tableName,
                'created_at'
        );

        $this->createIndex(
                'idx-lead-phone_number',
                $this->tableName,
                'phone_number'
        );
    }

    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
