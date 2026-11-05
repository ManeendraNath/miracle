<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Standard maintenance price: ₹4,000 per year. Only services with no price and a yearly (or global) cycle get it;
 * a price already set is left alone. They are billed from their next renewal.
 */
final class m261116_100000_set_standard_maintenance_price extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_cycle_months' => 12], ['service_type' => 3]);
        $this->update(
            '{{%maintenance}}',
            ['renewal_base_amount' => 4000, 'updated_at' => time()],
            ['and', ['or', ['renewal_base_amount' => null], ['renewal_base_amount' => 0]], ['or', ['renewal_cycle_months' => null], ['renewal_cycle_months' => 12]]],
        );
    }

    /** Data only, and which rows changed isn't recorded. */
    public function safeDown(): bool
    {
        return true;
    }
}
