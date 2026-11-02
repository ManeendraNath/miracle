<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * coupon_applicability and coupon_assigned_user had UNIQUE(coupon_id), so a coupon
 * could be limited to only one service type and assigned to only one customer.
 * Uniqueness moves to the pair. The new index starts with coupon_id, so the
 * foreign key on coupon_id keeps an index once the old one is dropped.
 */
class m261103_100000_allow_multiple_coupon_restrictions extends Migration
{
    private const PAIRS = [
        '{{%coupon_applicability}}' => ['uq-coupon_applicability-coupon_id', 'uq-coupon_applicability-coupon_type', 'item_type'],
        '{{%coupon_assigned_user}}' => ['uq-coupon_assigned_user-coupon_id', 'uq-coupon_assigned_user-coupon_user', 'user_id'],
    ];

    public function safeUp()
    {
        foreach (self::PAIRS as $table => [$old, $new, $column]) {
            $this->createIndex($new, $table, ['coupon_id', $column], true);
            $this->dropIndex($old, $table);
        }
    }

    public function safeDown()
    {
        foreach (self::PAIRS as $table => [$old, $new]) {
            $this->createIndex($old, $table, 'coupon_id', true);
            $this->dropIndex($new, $table);
        }
    }
}
