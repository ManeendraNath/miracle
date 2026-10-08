<?php

use yii\db\Migration;

/**
 * Handles the creation of high-fidelity automated invoicing and tax matrix ledger tracking.
 */
class m260929_110514_create_invoice_tables extends Migration
{
    private $tableName = '{{%invoice}}';

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // 1. INVOICES MASTER CONTROL TABLE
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer(11)->notNull(),
                'invoice_number' => $this->string(100)->notNull(),
                'client_name' => $this->string(255)->notNull(),
                'client_address_line_1' => $this->string(255)->defaultValue(null),
                'client_address_line_2' => $this->string(255)->defaultValue(null),
                
                // Advanced Normalized Financial Breakdown Precision Engine
                'subtotal_amount' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('Sum of all invoice items before taxes and discounts'),
                
                // Coupon & Discount System Implemented Fields
                'coupon_id' => $this->integer(11)->defaultValue(null)->comment('Maintains historical link back to coupons reference table if available'),
                'coupon_code' => $this->string(32)->defaultValue(null)->comment('Preserves coupon name snapshot text at point-of-sale for audit permanence'),
                'discount_amount' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('Total mathematical deduction applied via code or flat reduction adjustment'),
                
                // Taxation Engine Fields
                'cgst_percent' => $this->decimal(5, 2)->defaultValue(9.00),
                'sgst_percent' => $this->decimal(5, 2)->defaultValue(9.00),
                'igst_percent' => $this->decimal(5, 2)->defaultValue(0.00),
                
                'total_payable' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('Calculated formula: (Subtotal - Discount) + Taxes applied'),
                'status' => "ENUM('Unpaid', 'Paid', 'Partially_Paid', 'Cancelled', 'Refunded') NOT NULL DEFAULT 'Unpaid'",
                'due_date' => $this->date()->defaultValue(null),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ], $tableOptions);

            // Replaced with native framework built-in createIndex commands
            $this->createIndex('idx-invoice-user_id', $this->tableName, 'user_id');
            $this->createIndex('idx-invoice-status', $this->tableName, 'status');
            $this->createIndex('idx-invoice-coupon_id', $this->tableName, 'coupon_id');
            $this->createIndex('idx-unique-invoice_number', $this->tableName, 'invoice_number', true);
        }
    }

    public function down()
    {
        if ($this->db->getTableSchema($this->tableName, true) !== null) {
            $this->dropTable($this->tableName);
        }
    }
}
