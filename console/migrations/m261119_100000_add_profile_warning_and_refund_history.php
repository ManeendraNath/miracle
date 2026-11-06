<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * user.profile_warned_at: when the customer was first told the account can be disabled.
 * admin_notification.emailed_at: a once-a-day alert is retried until the email actually went out.
 * invoice.written_off_at: a balance forgiven, told apart from a refund.
 * transaction_refund: one row per refund, so the accounting export can show each in its own period.
 */
final class m261119_100000_add_profile_warning_and_refund_history extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'profile_warned_at', $this->integer()->null());
        $this->addColumn('{{%admin_notification}}', 'emailed_at', $this->integer()->null());
        // Older alerts count as delivered: nothing should be re-sent because of this column.
        $this->execute('UPDATE {{%admin_notification}} SET emailed_at = created_at');
        $this->addColumn('{{%invoice}}', 'written_off_at', $this->integer()->null());
        // Until now a closed invoice with no refund on it was how a write-off was recognised.
        $this->execute(
            "UPDATE {{%invoice}} i SET i.written_off_at = i.updated_at
             WHERE i.status = 'Refunded' AND NOT EXISTS (SELECT 1 FROM {{%transaction}} t WHERE t.invoice_id = i.id AND t.refunded_amount > 0 AND t.is_unapplied = 0)"
        );

        $this->createTable('{{%transaction_refund}}', [
            'id' => $this->primaryKey(),
            'transaction_id' => $this->integer()->notNull(),
            'amount' => $this->decimal(10, 2)->notNull(),
            'refunded_at' => $this->integer()->notNull(),
            'note' => $this->string(255)->null(),
        ]);
        $this->createIndex('idx_transaction_refund_transaction', '{{%transaction_refund}}', 'transaction_id');
        $this->createIndex('idx_transaction_refund_refunded_at', '{{%transaction_refund}}', 'refunded_at');
        $this->addForeignKey('fk_transaction_refund_transaction', '{{%transaction_refund}}', 'transaction_id', '{{%transaction}}', 'id', 'RESTRICT', 'CASCADE');
        // Earlier refunds only kept a running total and the last date.
        $this->execute(
            "INSERT INTO {{%transaction_refund}} (transaction_id, amount, refunded_at, note)
             SELECT id, refunded_amount, COALESCE(refunded_at, updated_at), 'Total recorded before refunds were listed individually'
             FROM {{%transaction}} WHERE refunded_amount > 0"
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%transaction_refund}}');
        $this->dropColumn('{{%invoice}}', 'written_off_at');
        $this->dropColumn('{{%admin_notification}}', 'emailed_at');
        $this->dropColumn('{{%user}}', 'profile_warned_at');
    }
}
