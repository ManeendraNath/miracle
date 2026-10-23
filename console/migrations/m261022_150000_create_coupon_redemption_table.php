<?php

declare(strict_types=1);

use yii\db\Migration;

final class m261022_150000_create_coupon_redemption_table extends Migration
{

    public function safeUp(): void
    {
        $this->createTable(
                '{{%coupon_redemption}}',
                [
                    'id' => $this->primaryKey(),
                    'coupon_id' => $this->integer()->notNull(),
                    'user_id' => $this->integer()->notNull(),
                    'invoice_id' => $this->integer()->notNull(),
                    'coupon_code' => $this->string(32)->notNull(),
                    'discount_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                    'redeemed_at' => $this->integer()->notNull(),
                ]
        );

        $this->createIndex(
                'uq_coupon_redemption_invoice',
                '{{%coupon_redemption}}',
                'invoice_id',
                true
        );

        $this->createIndex(
                'idx_coupon_redemption_coupon',
                '{{%coupon_redemption}}',
                'coupon_id'
        );

        $this->createIndex(
                'idx_coupon_redemption_coupon_user',
                '{{%coupon_redemption}}',
                [
                    'coupon_id',
                    'user_id',
                ]
        );

        $this->addForeignKey(
                'fk_coupon_redemption_coupon',
                '{{%coupon_redemption}}',
                'coupon_id',
                '{{%coupon}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );

        $this->addForeignKey(
                'fk_coupon_redemption_user',
                '{{%coupon_redemption}}',
                'user_id',
                '{{%user}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );

        $this->addForeignKey(
                'fk_coupon_redemption_invoice',
                '{{%coupon_redemption}}',
                'invoice_id',
                '{{%invoice}}',
                'id',
                'CASCADE',
                'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable(
                '{{%coupon_redemption}}'
        );
    }
}
