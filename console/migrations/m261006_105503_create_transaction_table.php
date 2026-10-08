<?php

use yii\db\Migration;

class m261006_105503_create_transaction_table extends Migration
{

    private $tableName = '{{%transaction}}';

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'invoice_id' => $this->integer(11)->notNull(),
                // Gateway Router Control Column
                'gateway_id' => $this->tinyInteger()->defaultValue(null)->comment('null/0: Manual, 1: Razorpay, 2: Personal UPI / Bank Deposit'),
                // Common Identifiers (Works for automated gateway IDs or Manual UPI UTR Reference Numbers)
                'gateway_payment_id' => $this->string(100)->defaultValue(null)->comment('Razorpay pay_id OR User-entered UPI Transaction/UTR Ref No'),
                'gateway_order_id' => $this->string(100)->defaultValue(null)->comment('Razorpay order_id reference token (null for Manual UPI)'),
                // Financial Ledger Blocks
                'amount' => $this->decimal(10, 2)->notNull(),
                'gateway_fee' => $this->decimal(10, 2)->defaultValue(0.00),
                'status' => "ENUM('Pending', 'Authorized', 'Captured', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending'",
                // Personal UPI / Bank Deposit Upload Handling Fields
                'user_payment_notes' => $this->text()->defaultValue(null)->comment('User remarks e.g., Sent from Rahul UPI wallet'),
                'payment_receipt_file' => $this->string(255)->defaultValue(null)->comment('File directory storage path path for uploaded snapshot proofs'),
                // Logging Payloads
                'raw_payload' => 'LONGTEXT NULL COMMENT \'JSON dump for automated webhooks or error trace tracking\'',
                'paid_at' => $this->integer(11)->defaultValue(null)->comment('Timestamp of confirmation/capture check approval'),
                'created_at' => $this->integer(11)->notNull(),
                'updated_at' => $this->integer(11)->notNull(),
            ]);

            $this->createIndex('idx-transaction-invoice_id', '{{%transaction}}', 'invoice_id');
            $this->createIndex('idx-transaction-gateway_id', '{{%transaction}}', 'gateway_id');
            $this->createIndex('idx-transaction-status', '{{%transaction}}', 'status');
            $this->createIndex('idx-transaction-payment_id', '{{%transaction}}', 'gateway_payment_id');
        }
    }

    public function down()
    {
        $this->dropTable($this->tableName);
    }
}
