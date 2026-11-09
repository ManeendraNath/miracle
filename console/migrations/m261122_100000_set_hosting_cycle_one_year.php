<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

/**
 * Hosting is billed yearly, and its price rises every two years (RenewalPriceCalculator).
 * A service with its own Renewal Cycle keeps it.
 * Hosting on the global cycle with no paid renewal yet bills its Renewal Base Amount for one year, and any Next Renewal
 * Price is billed for one year as entered; if staff entered a two-year price there, it must be halved by hand. Those services are listed and emailed.
 */
final class m261122_100000_set_hosting_cycle_one_year extends Migration
{
    private const HOSTING = 2;

    public function safeUp(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_cycle_months' => 12], ['service_type' => self::HOSTING]);

        $rows = (new Query())
            ->select(['h.primary_domain', 'h.renewal_base_amount', 'h.next_renewal_amount', 'h.current_expiry_date'])
            ->from(['h' => '{{%hosting}}'])
            ->where(['h.renewal_cycle_months' => null, 'h.status' => 1])
            ->andWhere(['or',
                // A one-time price is billed as entered, whatever was paid before.
                ['>', 'h.next_renewal_amount', 0],
                ['and', ['>', 'h.renewal_base_amount', 0], ['not exists', (new Query())->from(['e' => '{{%billing_event}}'])
                    ->where('e.item_id = h.id')->andWhere(['e.item_type' => self::HOSTING, 'e.status' => 'Fulfilled'])]],
            ])
            ->orderBy(['h.current_expiry_date' => SORT_ASC])
            ->all();
        if ($rows === []) {
            return;
        }
        $list = implode("\n", array_map(static fn(array $row): string => sprintf(
            '%s: Renewal Base Amount %s, Next Renewal Price %s, expires %s',
            $row['primary_domain'],
            $row['renewal_base_amount'] ?? 'not set',
            $row['next_renewal_amount'] ?? 'not set',
            $row['current_expiry_date']
        ), $rows));
        echo "    > Hosting now renews yearly. Check that these prices are for one year, not two:\n{$list}\n";
        \common\services\admin\AdminAlertService::hostingPricesToCheck($list);
    }

    public function safeDown(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_cycle_months' => 24], ['service_type' => self::HOSTING]);
    }
}
