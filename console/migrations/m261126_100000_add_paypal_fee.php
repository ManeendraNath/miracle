<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * PayPal's own fee, built into the US dollar price for clients abroad so the payment still brings in about the
 * invoice's rupee amount (shown as one total, never as a surcharge). And the rate locked on an invoice for a client
 * abroad when it is sent (invoice.usd_rate_locked until usd_rate_until), so the dollar price stays the same until
 * it is due. Can be run again after a failure.
 */
final class m261126_100000_add_paypal_fee extends Migration
{
    public function safeUp(): void
    {
        $table = $this->db->getTableSchema('{{%site_setting}}', true);
        if ($table->getColumn('paypal_fee_percent') === null) {
            $this->addColumn('{{%site_setting}}', 'paypal_fee_percent', $this->decimal(5, 2)->notNull()->defaultValue(4.4));
        }
        if ($table->getColumn('paypal_fee_fixed_usd') === null) {
            $this->addColumn('{{%site_setting}}', 'paypal_fee_fixed_usd', $this->decimal(5, 2)->notNull()->defaultValue(0.3));
        }
        $invoice = $this->db->getTableSchema('{{%invoice}}', true);
        if ($invoice->getColumn('usd_rate_locked') === null) {
            $this->addColumn('{{%invoice}}', 'usd_rate_locked', $this->decimal(10, 4)->null());
        }
        if ($invoice->getColumn('usd_rate_until') === null) {
            $this->addColumn('{{%invoice}}', 'usd_rate_until', $this->date()->null());
        }
    }

    public function safeDown(): void
    {
        foreach (['usd_rate_locked', 'usd_rate_until'] as $column) {
            if ($this->db->getTableSchema('{{%invoice}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%invoice}}', $column);
            }
        }
        foreach (['paypal_fee_percent', 'paypal_fee_fixed_usd'] as $column) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%site_setting}}', $column);
            }
        }
    }
}
