<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Invoices checked by staff before the customer sees them:
 * invoice.status 'Draft' (waiting for review), and site_setting.review_invoices to switch the step on or off (on).
 * Can be run again after a failure partway.
 */
final class m261124_100000_add_invoice_review extends Migration
{
    private const STATUSES = "'Unpaid','Partially_Paid','Paid','Carried_Forward','Cancelled','Refunded'";

    public function safeUp(): void
    {
        $this->execute('ALTER TABLE {{%invoice}} MODIFY `status` ENUM(' . self::STATUSES . ",'Draft') NOT NULL DEFAULT 'Unpaid'");
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('review_invoices') === null) {
            $this->addColumn('{{%site_setting}}', 'review_invoices', $this->boolean()->notNull()->defaultValue(1));
        }
    }

    public function safeDown(): void
    {
        if ((new \yii\db\Query())->from('{{%invoice}}')->where(['status' => 'Draft'])->exists($this->db)) {
            throw new \RuntimeException('Invoices are waiting for review: send or cancel them first.');
        }
        $this->execute('ALTER TABLE {{%invoice}} MODIFY `status` ENUM(' . self::STATUSES . ") NOT NULL DEFAULT 'Unpaid'");
        if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn('review_invoices') !== null) {
            $this->dropColumn('{{%site_setting}}', 'review_invoices');
        }
    }
}
