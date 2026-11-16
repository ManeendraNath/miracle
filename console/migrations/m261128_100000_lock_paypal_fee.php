<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The PayPal fee (Site Settings) locked with the dollar rate when an invoice for a client abroad is sent, so the
 * US$ price in the email holds even if the fee setting changes before it is paid. Invoices already locked get
 * today's fee, the one their price was quoted with. Can be run again after a failure.
 */
final class m261128_100000_lock_paypal_fee extends Migration
{
    public function safeUp(): void
    {
        $invoice = $this->db->getTableSchema('{{%invoice}}', true);
        if ($invoice->getColumn('usd_fee_percent_locked') === null) {
            $this->addColumn('{{%invoice}}', 'usd_fee_percent_locked', $this->decimal(5, 2)->null()->after('usd_rate_until'));
        }
        if ($invoice->getColumn('usd_fee_fixed_locked') === null) {
            $this->addColumn('{{%invoice}}', 'usd_fee_fixed_locked', $this->decimal(5, 2)->null()->after('usd_fee_percent_locked'));
        }
        $fee = $this->db->createCommand('SELECT paypal_fee_percent, paypal_fee_fixed_usd FROM {{%site_setting}} WHERE id = 1')->queryOne();
        if ($fee !== false) {
            $this->update(
                '{{%invoice}}',
                ['usd_fee_percent_locked' => $fee['paypal_fee_percent'], 'usd_fee_fixed_locked' => $fee['paypal_fee_fixed_usd']],
                ['and', ['not', ['usd_rate_locked' => null]], ['usd_fee_percent_locked' => null]],
            );
        }
    }

    public function safeDown(): void
    {
        foreach (['usd_fee_fixed_locked', 'usd_fee_percent_locked'] as $column) {
            if ($this->db->getTableSchema('{{%invoice}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%invoice}}', $column);
            }
        }
    }
}
