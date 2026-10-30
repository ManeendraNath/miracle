<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * A cancelled BillingEvent must not occupy its asset/period slot, otherwise the
 * period can never be invoiced again. `live_slot` is 1 for every non-cancelled
 * event and NULL for cancelled ones; unique indexes ignore NULLs, so at most one
 * live event exists per asset/period while any number of cancelled ones may.
 */
class m261030_100000_allow_rebilling_cancelled_billing_events extends Migration
{

    public function safeUp(): void
    {
        $this->execute(
                "ALTER TABLE {{%billing_event}} ADD COLUMN live_slot TINYINT"
                . " AS (IF(status = 'Cancelled', NULL, 1)) STORED"
        );
        $this->createIndex(
                'uq-billing_event-item-period-live',
                '{{%billing_event}}',
                ['item_type', 'item_id', 'period_start', 'period_end', 'live_slot'],
                true
        );
        $this->dropIndex('uq-billing_event-item-period', '{{%billing_event}}');
    }

    public function safeDown(): void
    {
        // Fails if a period has been re-billed after cancellation; resolve those rows first.
        $this->createIndex(
                'uq-billing_event-item-period',
                '{{%billing_event}}',
                ['item_type', 'item_id', 'period_start', 'period_end'],
                true
        );
        $this->dropIndex('uq-billing_event-item-period-live', '{{%billing_event}}');
        $this->dropColumn('{{%billing_event}}', 'live_slot');
    }
}
