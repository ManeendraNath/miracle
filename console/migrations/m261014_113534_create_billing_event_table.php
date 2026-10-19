<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Handles the creation of table `{{%billing_event}}`.
 */
class m261014_113534_create_billing_event_table extends Migration
{

    private const BILLING_SETTINGS = '{{%billing_settings}}';
    private const BILLING_EVENT = '{{%billing_event}}';

    public function safeUp(): void
    {
        /*
         * Global administrative billing defaults.
         *
         * These values act as defaults. Individual assets may override
         * them in future using asset-specific configuration.
         */
        $this->createTable(self::BILLING_SETTINGS, [
            'id' => $this->primaryKey(),
            'service_type' => $this->smallInteger()->notNull(),
            /*
             * Store cycles in months:
             *
             * 12 = yearly
             * 24 = every two years
             *
             * This is more flexible than storing "year" as a unit.
             */
            'renewal_cycle_months' => $this->integer()->notNull(),
            'renewal_increase_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0),
            /*
             * Number of days before service expiry that the service
             * becomes eligible for invoice generation.
             */
            'invoice_before_days' => $this->integer()->notNull()->defaultValue(30),
            /*
             * Invoice payment period after generation.
             */
            'payment_due_days' => $this->integer()->notNull()->defaultValue(15),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        /*
         * Only one global configuration record per service type.
         */
        $this->createIndex('uq-billing_settings-service_type', self::BILLING_SETTINGS, 'service_type', true);

        /*
         * Default configuration.
         *
         * Domain:
         *      12-month cycle
         *      10% increase
         *
         * Hosting:
         *      24-month cycle
         *      initial 10% default; admin can change it.
         */
        $now = time();

        $this->batchInsert(
                self::BILLING_SETTINGS,
                ['service_type', 'renewal_cycle_months', 'renewal_increase_percent',
                    'invoice_before_days', 'payment_due_days', 'is_active', 'created_at', 'updated_at'],
                [
                    [1, 12, 10.00, 30, 15, true, $now, $now],
                    [2, 24, 10.00, 30, 15, true, $now, $now],
                    [3, 12, 0.00, 30, 15, true, $now, $now],
                ]
        );

        /*
         * Immutable-ish financial billing event ledger.
         *
         * An event represents ONE service renewal for ONE service
         * period, regardless of which invoice eventually contains it.
         */
        $this->createTable(self::BILLING_EVENT, [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->integer()->notNull(),
            /*
             * Corresponds to InvoiceItem types:
             *
             * 1 = Domain
             * 2 = Hosting
             * 3 = Maintenance
             * 4 = Custom
             */
            'item_type' => $this->smallInteger()->notNull(),
            /*
             * ID of the underlying domain/hosting/maintenance record.
             */
            'item_id' => $this->integer()->notNull(),
            /*
             * Human-readable snapshot.
             *
             * This prevents future asset-name changes from making the
             * billing ledger impossible to understand.
             */
            'description' => $this->string(500)->notNull(),
            /*
             * Renewal period covered by this charge.
             */
            'period_start' => $this->date()->notNull(),
            'period_end' => $this->date()->notNull(),
            /*
             * Previous charge used as the escalation base.
             */
            'previous_amount' => $this->decimal(10, 2),
            /*
             * Percentage actually used for this calculation.
             */
            'increase_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0),
            /*
             * Calculated amount BEFORE any manual override.
             */
            'calculated_amount' => $this->decimal(10, 2)->notNull(),
            /*
             * Final amount invoiced.
             *
             * Normally equal to calculated_amount but may be manually
             * overridden by an administrator.
             */
            'amount' => $this->decimal(10, 2)->notNull(),
            'is_manual_amount' => $this->boolean()->notNull()->defaultValue(false),
            'due_date' => $this->date()->notNull(),
            /*
             * Null means not yet invoiced.
             */
            'invoice_id' => $this->integer(),
            'status' => "ENUM('Pending', 'Invoiced', 'Fulfilled', 'Cancelled') NOT NULL DEFAULT 'Pending'",
            'notes' => $this->string(500),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        /*
         * This is the primary idempotency boundary.
         *
         * The same physical asset cannot create two renewal events for
         * exactly the same service period.
         */
        $this->createIndex('uq-billing_event-item-period', self::BILLING_EVENT, ['item_type', 'item_id', 'period_start', 'period_end'], true);
        $this->createIndex('idx-billing_event-user-status', self::BILLING_EVENT, ['user_id', 'status']
        );
        $this->createIndex('idx-billing_event-invoice_id', self::BILLING_EVENT, 'invoice_id');
        $this->createIndex('idx-billing_event-due_date', self::BILLING_EVENT, 'due_date');
        $this->addForeignKey(
                'fk-billing_event-user',
                self::BILLING_EVENT,
                'user_id',
                '{{%user}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );

        $this->addForeignKey(
                'fk-billing_event-invoice',
                self::BILLING_EVENT,
                'invoice_id',
                '{{%invoice}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
    }

    #[\Override]
    public function safeDown(): void
    {
        $this->dropForeignKey('fk-billing_event-invoice', self::BILLING_EVENT);
        $this->dropForeignKey('fk-billing_event-user', self::BILLING_EVENT);
        $this->dropTable(self::BILLING_EVENT);
        $this->dropTable(self::BILLING_SETTINGS);
    }
}
