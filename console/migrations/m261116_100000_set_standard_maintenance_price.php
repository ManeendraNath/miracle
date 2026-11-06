<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

/**
 * Standard maintenance price: ₹4,000 per year. Only services with no price and a yearly (or global) cycle get it;
 * a price already set is left alone. They are billed from their next renewal. One already past its expiry is left
 * unpriced and listed for the admins: pricing it would bill the months since then straight away.
 */
final class m261116_100000_set_standard_maintenance_price extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_cycle_months' => 12], ['service_type' => 3]);
        $blank = ['and', ['or', ['renewal_base_amount' => null], ['renewal_base_amount' => 0]], ['or', ['renewal_cycle_months' => null], ['renewal_cycle_months' => 12]]];
        $today = date('Y-m-d');
        $this->update('{{%maintenance}}', ['renewal_base_amount' => 4000, 'updated_at' => time()], ['and', $blank, ['>=', 'current_expiry_date', $today]]);
        $pastExpiry = (new Query())->select(['website_url', 'current_expiry_date'])->from('{{%maintenance}}')->where(['and', $blank, ['<', 'current_expiry_date', $today]])->all($this->db);
        if ($pastExpiry !== []) {
            $list = implode("\n", array_map(static fn(array $row): string => $row['website_url'] . ' (expired ' . $row['current_expiry_date'] . ')', $pastExpiry));
            echo "    > Past expiry, left with no price:\n{$list}\n";
            $this->insert('{{%admin_notification}}', [
                'type' => 'billing',
                'title' => 'Maintenance Without A Price',
                'message' => "These maintenance services are past their expiry and were left with no renewal price, so they won't be billed until you set one:\n{$list}",
                'route' => '/maintenance/index',
                'is_read' => 0,
                'created_at' => time(),
            ]);
        }
    }

    /** Data only, and which rows changed isn't recorded. */
    public function safeDown(): bool
    {
        return true;
    }
}
