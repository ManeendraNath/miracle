<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Invoices priced in US dollars for clients abroad. The books stay in rupees: each line's rupee price is its dollar
 * price at the reference rate of the invoice date (usd_rate_locked, with that rate's date and source), so the client
 * pays exactly usd_total by PayPal. Can be run again after a failure.
 */
final class m261203_100000_add_usd_priced_invoices extends Migration
{
    public function safeUp(): void
    {
        $invoice = $this->db->getTableSchema('{{%invoice}}', true);
        if ($invoice->getColumn('usd_total') === null) {
            $this->addColumn('{{%invoice}}', 'usd_total', $this->decimal(12, 2)->null()->after('total_payable'));
        }
        if ($invoice->getColumn('usd_rate_date') === null) {
            $this->addColumn('{{%invoice}}', 'usd_rate_date', $this->date()->null()->after('usd_rate_until'));
        }
        if ($invoice->getColumn('usd_rate_source') === null) {
            $this->addColumn('{{%invoice}}', 'usd_rate_source', $this->string(60)->null()->after('usd_rate_date'));
        }
        $item = $this->db->getTableSchema('{{%invoice_item}}', true);
        if ($item->getColumn('usd_unit_price') === null) {
            $this->addColumn('{{%invoice_item}}', 'usd_unit_price', $this->decimal(12, 2)->null()->after('unit_price'));
        }
        if ($item->getColumn('usd_total_price') === null) {
            $this->addColumn('{{%invoice_item}}', 'usd_total_price', $this->decimal(12, 2)->null()->after('total_price'));
        }
    }

    public function safeDown(): void
    {
        foreach (['invoice_item' => ['usd_total_price', 'usd_unit_price'], 'invoice' => ['usd_rate_source', 'usd_rate_date', 'usd_total']] as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->db->getTableSchema('{{%' . $table . '}}', true)->getColumn($column) !== null) {
                    $this->dropColumn('{{%' . $table . '}}', $column);
                }
            }
        }
    }
}
