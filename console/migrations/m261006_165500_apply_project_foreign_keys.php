<?php

use yii\db\Migration;

class m261006_165500_apply_project_foreign_keys extends Migration
{

    private $tableName = '{{%maintenance}}';

    public function up()
    {
        // 1. Link Service Assets back to their respective Owners (user table)
        $this->addForeignKey('fk-domains-user_id', '{{%domains}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-hosting-user_id', '{{%hosting}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-maintenance-user_id', '{{%maintenance}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        // 2. Link Master Invoices to Customers & Promo Systems
        $this->addForeignKey('fk-invoice-user_id', '{{%invoice}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-invoice-coupon_id', '{{%invoice}}', 'coupon_id', '{{%coupons}}', 'coupon_id', 'SET NULL', 'CASCADE');

        // 3. Link Breakdown Elements to the parent Statement Ledger
        $this->addForeignKey('fk-invoice_item-invoice_id', '{{%invoice_item}}', 'invoice_id', '{{%invoice}}', 'id', 'CASCADE', 'CASCADE');

        // 4. Link Financial Processing Gateway Records to the target Invoice
        $this->addForeignKey('fk-transaction-invoice_id', '{{%transaction}}', 'invoice_id', '{{%invoice}}', 'id', 'RESTRICT', 'CASCADE');
        
        // 5. Link Lifecycle Renewal History records back to Bills/Transactions
        $this->addForeignKey('fk-renewal_history-invoice_id', '{{%service_renewal_history}}', 'invoice_id', '{{%invoice}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-renewal_history-transaction_id', '{{%service_renewal_history}}', 'transaction_id', '{{%transaction}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        // Unbind constraints in safe reverse-order sequence to prevent SQL drop deadlocks
        $this->dropForeignKey('fk-renewal_history-transaction_id', '{{%service_renewal_history}}');
        $this->dropForeignKey('fk-renewal_history-invoice_id', '{{%service_renewal_history}}');
        $this->dropForeignKey('fk-transaction-invoice_id', '{{%transaction}}');
        $this->dropForeignKey('fk-invoice_item-invoice_id', '{{%invoice_item}}');
        $this->dropForeignKey('fk-invoice-coupon_id', '{{%invoice}}');
        $this->dropForeignKey('fk-invoice-user_id', '{{%invoice}}');
        $this->dropForeignKey('fk-maintenance-user_id', '{{%maintenance}}');
        $this->dropForeignKey('fk-hosting-user_id', '{{%hosting}}');
        $this->dropForeignKey('fk-domains-user_id', '{{%domains}}');
    }
}
