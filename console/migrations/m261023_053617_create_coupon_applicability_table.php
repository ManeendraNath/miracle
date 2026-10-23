<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%coupon_applicability}}`.
 */
class m261023_053617_create_coupon_applicability_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%coupon_applicability}}', [
            'id' => $this->primaryKey(),
            'coupon_id' => $this->integer()->notNull(),
            'item_type' => $this->integer()->notNull(),
            'item_id' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
                'uq-coupon_applicability-coupon_id',
                '{{%coupon_applicability}}',
                'coupon_id',
                true
        );

        $this->addForeignKey(
                'fk-coupon_applicability-coupon_id',
                '{{%coupon_applicability}}',
                'coupon_id',
                '{{%coupon}}',
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
                'fk-coupon_applicability-coupon_id',
                '{{%coupon_applicability}}'
        );

        $this->dropTable('{{%coupon_applicability}}');
    }
}
