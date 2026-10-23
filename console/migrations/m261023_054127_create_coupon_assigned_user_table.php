<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%coupon_assigned_user}}`.
 */
class m261023_054127_create_coupon_assigned_user_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%coupon_assigned_user}}', [
            'id' => $this->primaryKey(),
            'coupon_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'assigned_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
                'uq-coupon_assigned_user-coupon_id',
                '{{%coupon_assigned_user}}',
                'coupon_id',
                true
        );

        $this->addForeignKey(
                'fk-coupon_assigned_user-coupon_id',
                '{{%coupon_assigned_user}}',
                'coupon_id',
                '{{%coupon}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
        
        $this->addForeignKey(
                'fk-coupon_assigned_user-user_id',
                '{{%coupon_assigned_user}}',
                'user_id',
                '{{%user}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
                'fk-coupon_assigned_user-coupon_id',
                '{{%coupon_assigned_user}}'
        );
        
        $this->dropForeignKey(
                'fk-coupon_assigned_user-user_id',
                '{{%coupon_assigned_user}}'
        );
        
        $this->dropTable('{{%coupon_assigned_user}}');
    }
}
