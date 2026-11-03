<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Gap-free invoice serials per Indian financial year (April-March), as GST requires:
 * InvoiceNumberService locks this row inside the invoice's own transaction, so a
 * rolled-back invoice also rolls its number back. Existing invoices keep their numbers.
 */
final class m261108_100000_create_invoice_sequence extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%invoice_sequence}}', [
            'financial_year' => $this->char(4)->notNull()->comment('e.g. 2627 = April 2026 to March 2027'),
            'last_number' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'PRIMARY KEY ([[financial_year]])',
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%invoice_sequence}}');
    }
}
