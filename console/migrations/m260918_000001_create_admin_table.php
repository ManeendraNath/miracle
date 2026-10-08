<?php

use yii\db\Migration;

/**
 * Handles the creation of the multi-role administration auth layer.
 */
class m260918_000001_create_admin_table extends Migration
{

    private $tableName = '{{%admin}}';

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
                'username' => $this->string()->notNull()->unique(),
                'email' => $this->string()->notNull()->unique(),
                'auth_key' => $this->string(32)->notNull(),
                'password_hash' => $this->string()->notNull(),
                'password_reset_token' => $this->string()->unique(),
                // MULTI-ROLE TIERS SYSTEM FIELD
                // Defaults securely to lowest privilege tier 'Manager' until upgraded
                'role' => $this->string(50)->notNull()->defaultValue('Manager'),
                'status' => $this->smallInteger()->notNull()->defaultValue(10),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
                    ], $tableOptions);

            // PERFORMANCE INDEXES: Accelerates authentication queries inside Docker/Shared DBs
            $this->createIndex('{{%idx-admin-status}}', '{{%admin}}', 'status');
            $this->createIndex('{{%idx-admin-role}}', '{{%admin}}', 'role');

            // DEFAULT SUPERADMIN SEED LOGIC
            // Password defaults to: Maneendra@123
            // Generates an out-of-the-box account so you don't get locked out on your fresh volumes
            $this->insert($this->tableName, [
                'username' => 'superadmin',
                'email' => 'miraclewebtechnologies@gmail.com',
                'auth_key' => Yii::$app->security->generateRandomString(),
                'password_hash' => Yii::$app->security->generatePasswordHash('Maneendra@123'),
                'role' => 'Superadmin',
                'status' => 10,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
