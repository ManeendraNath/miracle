<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * user.profile_warned_at: when the customer was first told the account can be disabled.
 * user.reactivated_at: staff set a suspended account Active; another month runs from here.
 * admin_notification.emailed_at: an alert's email is retried until it actually went out.
 * invoice.written_off_at: a balance forgiven, told apart from a refund.
 * transaction_refund: one row per refund, so the accounting export can show each in its own period.
 *
 * MySQL DDL isn't transactional, so each step can be run again after a failure partway.
 */
final class m261119_100000_add_profile_warning_and_refund_history extends Migration
{
    public function safeUp(): void
    {
        $this->addColumnIfMissing('{{%user}}', 'profile_warned_at');
        $this->addColumnIfMissing('{{%user}}', 'reactivated_at');
        if ($this->addColumnIfMissing('{{%admin_notification}}', 'emailed_at')) {
            // Older alerts count as delivered: nothing should be re-sent because of this column.
            $this->execute('UPDATE {{%admin_notification}} SET emailed_at = created_at');
        }
        if ($this->addColumnIfMissing('{{%invoice}}', 'written_off_at')) {
            // Until now a closed invoice with no refund on it was how a write-off was recognised. One carried into an
            // invoice closed with a refund was closed along with that refund, not written off.
            // ponytail: one step of the chain; older links of a long chain may read as written off.
            $this->execute(
                "UPDATE {{%invoice}} i SET i.written_off_at = i.updated_at
                 WHERE i.status = 'Refunded'
                 AND NOT EXISTS (SELECT 1 FROM {{%transaction}} t WHERE t.invoice_id = i.id AND t.refunded_amount > 0 AND t.is_unapplied = 0)
                 AND NOT EXISTS (SELECT 1 FROM {{%invoice_item}} ii JOIN {{%transaction}} c ON c.invoice_id = ii.invoice_id
                     WHERE ii.source_invoice_id = i.id AND ii.item_type = 5 AND c.refunded_amount > 0 AND c.is_unapplied = 0)"
            );
        }

        if ($this->db->getTableSchema('{{%transaction_refund}}', true) === null) {
            // One statement, so it can't stop with the table there and its keys missing.
            $this->createTable('{{%transaction_refund}}', [
                'id' => $this->primaryKey(),
                'transaction_id' => $this->integer()->notNull(),
                'amount' => $this->decimal(10, 2)->notNull(),
                'refunded_at' => $this->integer()->notNull(),
                'note' => $this->string(255)->null(),
                'KEY idx_transaction_refund_transaction (transaction_id)',
                'KEY idx_transaction_refund_refunded_at (refunded_at)',
                'CONSTRAINT fk_transaction_refund_transaction FOREIGN KEY (transaction_id) REFERENCES {{%transaction}} (id) ON DELETE RESTRICT ON UPDATE CASCADE',
            ]);
        }
        // Earlier refunds only kept a running total and the last date. No refund can have been written yet on a re-run.
        $this->execute(
            "INSERT INTO {{%transaction_refund}} (transaction_id, amount, refunded_at, note)
             SELECT t.id, t.refunded_amount, COALESCE(t.refunded_at, t.updated_at), 'Total recorded before refunds were listed individually'
             FROM {{%transaction}} t WHERE t.refunded_amount > 0
             AND NOT EXISTS (SELECT 1 FROM {{%transaction_refund}} r WHERE r.transaction_id = t.id)"
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%transaction_refund}}');
        $this->dropColumn('{{%invoice}}', 'written_off_at');
        $this->dropColumn('{{%admin_notification}}', 'emailed_at');
        $this->dropColumn('{{%user}}', 'reactivated_at');
        $this->dropColumn('{{%user}}', 'profile_warned_at');
    }

    private function addColumnIfMissing(string $table, string $column): bool
    {
        if ($this->db->getTableSchema($table, true)->getColumn($column) !== null) {
            return false;
        }
        $this->addColumn($table, $column, $this->integer()->null());

        return true;
    }
}
