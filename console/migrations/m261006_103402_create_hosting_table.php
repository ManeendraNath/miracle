<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%hosting}}`.
 */
class m261006_103402_create_hosting_table extends Migration
{

    private $tableName = '{{%hosting}}';

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
                'id' => $this->primaryKey(),
                'user_id' => $this->integer(11)->notNull(),
                'primary_domain' => $this->string(255)->notNull(),
                'cpanel_ip' => $this->string(45)->defaultValue(null),
                'cpanel_username' => $this->string(100)->defaultValue(null),
                'cpanel_password' => $this->string(255)->defaultValue(null), // Encrypted
                'current_expiry_date' => $this->date()->notNull(),
                'status' => $this->smallInteger(2)->notNull()->defaultValue(1)->comment('0: Inactive, 1: Active, 2: Suspended/Expired'),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ], $tableOptions);
            
            $this->createIndex('idx-hosting-user_id', '{{%hosting}}', 'user_id');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
