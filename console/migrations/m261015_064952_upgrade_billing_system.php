<?php

declare(strict_types=1);

use yii\db\Migration;

class m261015_064952_upgrade_billing_system extends Migration
{

    public function safeUp(): void
    {
        $this->createBillingRun();
        $this->createCustomerBillingProfile();
        $this->upgradeBillingEvent();
        $this->upgradeInvoice();
        $this->upgradeInvoiceItem();
        $this->upgradeRenewalHistory();
        $this->upgradeTransaction();
        $this->seedBillingSettings();
    }

    public function safeDown(): void
    {
        throw new RuntimeException(
                        'Billing upgrade is intentionally irreversible because it modifies financial ledger structures.'
                );
    }

    private function createBillingRun(): void
    {
        $this->createTable('{{%billing_run}}', [
            'id' => $this->bigPrimaryKey(),
            'run_number' => $this->string(100)->notNull(),
            'status' => "ENUM('Running', 'Completed', 'Failed') NOT NULL DEFAULT 'Running'",
            'started_at' => $this->integer()->notNull(),
            'completed_at' => $this->integer()->null(),
            'failure_message' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq-billing_run-run_number', '{{%billing_run}}', 'run_number', true);

        $this->createIndex('idx-billing_run-status', '{{%billing_run}}', 'status');
    }

    private function createCustomerBillingProfile(): void
    {
        $this->createTable('{{%customer_billing_profile}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'legal_name' => $this->string(255)->notNull(),
            'address_line_1' => $this->string(255)->null(),
            'address_line_2' => $this->string(255)->null(),
            'city' => $this->string(100)->null(),
            'state' => $this->string(100)->notNull(),
            'state_code' => $this->string(10)->null(),
            'postal_code' => $this->string(20)->null(),
            'gstin' => $this->string(15)->null(),
            'pan' => $this->string(10)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq-customer_billing_profile-user', '{{%customer_billing_profile}}', 'user_id', true);

        $this->addForeignKey('fk-customer_billing_profile-user', '{{%customer_billing_profile}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    private function upgradeBillingEvent(): void
    {
        $this->addColumn('{{%billing_event}}', 'billing_run_id', $this->bigInteger()->null()->after('id'));

        $this->createIndex('idx-billing_event-run-user', '{{%billing_event}}', ['billing_run_id', 'user_id']);

        $this->addForeignKey('fk-billing_event-run', '{{%billing_event}}', 'billing_run_id', '{{%billing_run}}', 'id', 'RESTRICT', 'CASCADE');
    }

    private function upgradeInvoice(): void
    {
        $this->alterColumn(
                '{{%invoice}}',
                'status',
                "ENUM(
                'Unpaid',
                'Partially_Paid',
                'Paid',
                'Carried_Forward',
                'Cancelled',
                'Refunded'
            ) NOT NULL DEFAULT 'Unpaid'"
        );

        $this->addColumn(
                '{{%invoice}}',
                'billing_run_id',
                $this->bigInteger()->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_city',
                $this->string(100)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_state',
                $this->string(100)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_state_code',
                $this->string(10)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_postal_code',
                $this->string(20)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_gstin',
                $this->string(15)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'client_pan',
                $this->string(10)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'place_of_supply',
                $this->string(100)->null()
        );

        $this->addColumn(
                '{{%invoice}}',
                'emailed_at',
                $this->integer()->null()
        );

        $this->createIndex(
                'uq-invoice-invoice_number',
                '{{%invoice}}',
                'invoice_number',
                true
        );

        $this->createIndex(
                'idx-invoice-run-user',
                '{{%invoice}}',
                [
                    'billing_run_id',
                    'user_id',
                ]
        );

        $this->createIndex(
                'idx-invoice-emailed_at',
                '{{%invoice}}',
                'emailed_at'
        );

        $this->addForeignKey(
                'fk-invoice-billing_run',
                '{{%invoice}}',
                'billing_run_id',
                '{{%billing_run}}',
                'id',
                'SET NULL',
                'CASCADE'
        );
    }

    private function upgradeInvoiceItem(): void
    {
        $this->addColumn(
                '{{%invoice_item}}',
                'source_invoice_id',
                $this->integer()->null()
        );

        $this->addColumn(
                '{{%invoice_item}}',
                'billing_event_id',
                $this->bigInteger()->null()
        );

        $this->createIndex(
                'uq-invoice_item-billing_event',
                '{{%invoice_item}}',
                'billing_event_id',
                true
        );

        $this->createIndex(
                'uq-invoice_item-source_invoice',
                '{{%invoice_item}}',
                'source_invoice_id',
                true
        );

        $this->addForeignKey(
                'fk-invoice_item-source_invoice',
                '{{%invoice_item}}',
                'source_invoice_id',
                '{{%invoice}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );

        $this->addForeignKey(
                'fk-invoice_item-billing_event',
                '{{%invoice_item}}',
                'billing_event_id',
                '{{%billing_event}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
    }

    private function upgradeRenewalHistory(): void
    {
        $this->addColumn(
                '{{%service_renewal_history}}',
                'billing_event_id',
                $this->bigInteger()->null()
        );

        $this->createIndex(
                'uq-renewal_history-billing_event',
                '{{%service_renewal_history}}',
                'billing_event_id',
                true
        );

        $this->addForeignKey(
                'fk-renewal_history-billing_event',
                '{{%service_renewal_history}}',
                'billing_event_id',
                '{{%billing_event}}',
                'id',
                'RESTRICT',
                'CASCADE'
        );
    }

    private function upgradeTransaction(): void
    {
        $this->createIndex(
                'idx-transaction-invoice-status',
                '{{%transaction}}',
                [
                    'invoice_id',
                    'status',
                ]
        );

        $this->createIndex(
                'uq-transaction-gateway-order-id',
                '{{%transaction}}',
                'gateway_order_id',
                true
        );

        $this->dropIndex(
                'idx-transaction-payment_id',
                '{{%transaction}}'
        );

        $this->createIndex(
                'uq-transaction-gateway-payment-id',
                '{{%transaction}}',
                'gateway_payment_id',
                true
        );
    }

    private function seedBillingSettings(): void
    {
        $now = time();

        $rows = [
            [
                1,
                12,
                10.00,
                30,
                15,
                1,
                $now,
                $now,
            ],
            [
                2,
                24,
                0.00,
                30,
                15,
                1,
                $now,
                $now,
            ],
            [
                3,
                12,
                10.00,
                30,
                15,
                1,
                $now,
                $now,
            ],
        ];

        foreach ($rows as $row) {
            $exists = $this->db
                    ->createCommand(
                            'SELECT id
                     FROM {{%billing_settings}}
                     WHERE service_type = :type'
                    )
                    ->bindValue(':type', $row[0])
                    ->queryScalar();

            if ($exists !== false) {
                continue;
            }

            $this->insert('{{%billing_settings}}', [
                'service_type' => $row[0],
                'renewal_cycle_months' => $row[1],
                'renewal_increase_percent' => $row[2],
                'invoice_before_days' => $row[3],
                'payment_due_days' => $row[4],
                'is_active' => $row[5],
                'created_at' => $row[6],
                'updated_at' => $row[7],
            ]);
        }
    }
}
