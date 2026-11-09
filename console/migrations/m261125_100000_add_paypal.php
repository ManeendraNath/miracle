<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Payment gateways switched on in Site Settings per client location (keys stay in .env), and the US dollar rate
 * for PayPal: fetched daily (less a margin for PayPal's conversion) or set by hand. Can be run again after a failure.
 * Razorpay starts as it was: on for everyone if RAZORPAY_ENABLED was true.
 */
final class m261125_100000_add_paypal extends Migration
{
    public function safeUp(): void
    {
        $razorpayOn = (int) filter_var($_ENV['RAZORPAY_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
        foreach ($this->columns($razorpayOn) as $name => $type) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($name) === null) {
                $this->addColumn('{{%site_setting}}', $name, $type);
            }
        }
    }

    public function safeDown(): void
    {
        foreach (array_keys($this->columns(0)) as $name) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($name) !== null) {
                $this->dropColumn('{{%site_setting}}', $name);
            }
        }
    }

    private function columns(int $razorpayOn): array
    {
        return [
            'razorpay_india' => $this->boolean()->notNull()->defaultValue($razorpayOn),
            'razorpay_abroad' => $this->boolean()->notNull()->defaultValue($razorpayOn),
            'paypal_abroad' => $this->boolean()->notNull()->defaultValue(0),
            'usd_rate_auto' => $this->boolean()->notNull()->defaultValue(1),
            'usd_rate_margin' => $this->decimal(5, 2)->notNull()->defaultValue(3),
            'usd_inr_rate' => $this->decimal(10, 4)->null(),
            'usd_market_rate' => $this->decimal(10, 4)->null(),
            'usd_rate_checked_at' => $this->integer()->null(),
            'usd_rate_fetched_at' => $this->integer()->null(),
        ];
    }
}
