<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%domains}}`.
 */
class m220515_155701_create_domains_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%domains}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%domains}}');
    }
}
