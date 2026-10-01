<?php

use yii\db\Migration;

/**
 * Handles the creation of the public global metadata parameters infrastructure.
 */
class m260918_000002_create_site_setting_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%site_setting}}', [
            'id' => $this->primaryKey(),
            'site_name' => $this->string()->notNull(),
            'site_url' => $this->string()->notNull(),
            'mobile_1' => $this->bigInteger()->notNull(),
            'mobile_2' => $this->bigInteger()->null(),
            'email_1' => $this->string()->notNull(),
            'email_2' => $this->string()->null(),
            'email_3' => $this->string()->null(),
            'address_line_1' => $this->string()->null(),
            'address_line_2' => $this->string()->null(),
            'address_line_3' => $this->string()->null(),
            'facebook' => $this->string()->null(),
            'x' => $this->string()->null(),
            'youtube' => $this->string()->null(),
            'linkedin' => $this->string()->null(),
            'instagram' => $this->string()->null(),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);

        // Seed initial mock layout parameters mapping out-of-the-box
        $this->insert('{{%site_setting}}', [
            'site_name' => 'Miracle Web Technologies',
            'site_url' => 'https://miraclewebtechnologies.com',
            'mobile_1' => 919654511842,
            'email_1' => 'info@miraclewebtechnologies.com',
            'address_line_1' => 'A-26B, Street No. 2,',
            'address_line_2' => 'East Krishna Nagar,',
            'address_line_3' => 'Delhi-110051',
            'updated_at' => time(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropTable('{{%site_setting}}');
    }
}
