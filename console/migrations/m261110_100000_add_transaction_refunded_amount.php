<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * How much of a payment has been returned. A partial refund keeps the payment Captured with
 * refunded_amount > 0; a full refund sets status Refunded. Existing refunds are backfilled as full.
 */
final class m261110_100000_add_transaction_refunded_amount extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%transaction}}', 'refunded_amount', $this->decimal(10, 2)->notNull()->defaultValue(0)->after('gateway_fee'));
        $this->execute("UPDATE {{%transaction}} SET refunded_amount = amount WHERE status = 'Refunded'");
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%transaction}}', 'refunded_amount');
    }
}
