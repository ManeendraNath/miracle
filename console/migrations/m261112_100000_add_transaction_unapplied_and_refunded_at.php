<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * is_unapplied: an online payment captured after its invoice was settled or closed (held for refund or
 * applying to another invoice). Was marked by "UNAPPLIED:" in user_payment_notes, which customers can type.
 * refunded_at: when the last refund was recorded, so the accounting export can date refunds.
 */
final class m261112_100000_add_transaction_unapplied_and_refunded_at extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%transaction}}', 'is_unapplied', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('status'));
        $this->addColumn('{{%transaction}}', 'refunded_at', $this->integer()->null()->after('refunded_amount'));
        $this->execute("UPDATE {{%transaction}} SET is_unapplied = 1 WHERE gateway_id = 1 AND user_payment_notes LIKE BINARY '%UNAPPLIED:%'");
        $this->execute("UPDATE {{%transaction}} SET refunded_at = updated_at WHERE refunded_amount > 0");
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%transaction}}', 'refunded_at');
        $this->dropColumn('{{%transaction}}', 'is_unapplied');
    }
}
