<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The day of month a service renews on, so monthly cycles don't drift after a short month
 * (30 Jan -> 28 Feb -> 30 Mar, not 31 Mar). 31 = month end. Set when staff set the expiry date;
 * renewals keep it. Backfilled from the current expiry (a past drift can't be recovered).
 */
final class m261109_100000_add_renewal_anchor_day extends Migration
{
    private const TABLES = ['{{%domains}}', '{{%hosting}}', '{{%maintenance}}'];

    public function safeUp(): void
    {
        foreach (self::TABLES as $table) {
            $this->addColumn($table, 'renewal_anchor_day', $this->tinyInteger()->unsigned()->null()->after('current_expiry_date'));
            $this->execute("UPDATE {$table} SET renewal_anchor_day = IF(current_expiry_date = LAST_DAY(current_expiry_date), 31, DAY(current_expiry_date)) WHERE current_expiry_date IS NOT NULL");
        }
    }

    public function safeDown(): void
    {
        foreach (self::TABLES as $table) {
            $this->dropColumn($table, 'renewal_anchor_day');
        }
    }
}
