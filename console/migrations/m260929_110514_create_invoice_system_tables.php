<?php

use yii\db\Migration;

/**
 * Handles the creation of high-fidelity automated invoicing and tax matrix ledger tracking.
 */
class m260929_110514_create_invoice_system_tables extends Migration
{

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // 1. INVOICES MASTER CONTROL TABLE
        $this->createTable('{{%invoice}}', [
            'id' => $this->primaryKey(),
            'invoice_number' => $this->string(100)->notNull()->unique(),
            'client_name' => $this->string(255)->notNull(),
            'client_address_line_1' => $this->string(255)->null(),
            'client_address_line_2' => $this->string(255)->null(),
            'discount_amount' => $this->decimal(10, 2)->defaultValue(0.00),
            // 📊 COMPLIANT TAX MATRIX COLUMNS
            'cgst_percent' => $this->decimal(5, 2)->defaultValue(9.00), // Central GST Default (e.g. 9%)
            'sgst_percent' => $this->decimal(5, 2)->defaultValue(9.00), // State GST Default (e.g. 9%)
            'igst_percent' => $this->decimal(5, 2)->defaultValue(0.00), // Inter-state GST
            'status' => $this->string(50)->defaultValue('Unpaid'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
                ], $tableOptions);

        // 2. INVOICE LINE ITEMS MAPPING TABLE
        $this->createTable('{{%invoice_item}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'category' => $this->string(100)->notNull(), // e.g. Domain, Hosting, Development
            'description' => $this->text()->notNull(),
            'duration_quantity' => $this->string(100)->defaultValue('1 Year'),
            'unit_price' => $this->decimal(10, 2)->notNull(),
            'total_price' => $this->decimal(10, 2)->notNull(),
                ], $tableOptions);

        // Add foreign key constraint to link line items securely to parent invoices
        $this->addForeignKey(
                '{{%fk-invoice_item-invoice_id}}',
                '{{%invoice_item}}',
                'invoice_id',
                '{{%invoice}}',
                'id',
                'CASCADE'
        );

        // Insert initial demo seed records matching Creta Packaging's billing lifecycle
        $this->insert('{{%invoice}}', [
            'invoice_number' => 'MWT1011',
            'client_name' => 'Creta Packaging',
            'client_address_line_1' => 'New Delhi, India',
            'discount_amount' => 300.00,
            'cgst_percent' => 0.00, // Matching your original 0% template rule
            'sgst_percent' => 0.00,
            'igst_percent' => 0.00,
            'status' => 'Paid',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $parentInvoiceId = $this->db->getLastInsertID();

        $this->insert('{{%invoice_item}}', [
            'invoice_id' => $parentInvoiceId,
            'category' => 'Domain',
            'description' => 'cretapackaging.com domain renewal parameters',
            'duration_quantity' => '1 Year',
            'unit_price' => 1800.00,
            'total_price' => 1800.00,
        ]);

        $this->insert('{{%invoice_item}}', [
            'invoice_id' => $parentInvoiceId,
            'category' => 'Hosting',
            'description' => 'cretapackaging.com business high-performance reseller hosting array',
            'duration_quantity' => '1 Year',
            'unit_price' => 2000.00,
            'total_price' => 2000.00,
        ]);
    }

    public function down()
    {
        $this->dropTable('{{%invoice_item}}');
        $this->dropTable('{{%invoice}}');
    }
}
