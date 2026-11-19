<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Fixed-price services sold like store items (product.kind = service): how many working days the job takes, and on
 * the order the buyer's brief, their website, and the support ticket opened for the job once it is paid.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261214_100000_store_services extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%product}}', true)->getColumn('delivery_days') === null) {
            // Services only: working days from payment (and the buyer's details) to delivery.
            $this->addColumn('{{%product}}', 'delivery_days', $this->smallInteger()->null()->after('version'));
        }
        $order = $this->db->getTableSchema('{{%product_order}}', true);
        if ($order->getColumn('brief') === null) {
            // Services only: what the buyer needs done.
            $this->addColumn('{{%product_order}}', 'brief', $this->text()->null()->after('licence'));
            $this->addColumn('{{%product_order}}', 'website_url', $this->string(255)->null()->after('brief'));
            $this->addColumn('{{%product_order}}', 'support_ticket_id', $this->integer()->null()->after('website_url'));
        }
        if (!isset($this->db->getTableSchema('{{%product_order}}', true)->foreignKeys['fk-product_order-support_ticket'])) {
            $this->addForeignKey('fk-product_order-support_ticket', '{{%product_order}}', 'support_ticket_id', '{{%support_ticket}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if (isset($this->db->getTableSchema('{{%product_order}}', true)->foreignKeys['fk-product_order-support_ticket'])) {
            $this->dropForeignKey('fk-product_order-support_ticket', '{{%product_order}}');
        }
        if ($this->db->getTableSchema('{{%product_order}}', true)->getColumn('brief') !== null) {
            $this->dropColumn('{{%product_order}}', 'support_ticket_id');
            $this->dropColumn('{{%product_order}}', 'website_url');
            $this->dropColumn('{{%product_order}}', 'brief');
        }
        if ($this->db->getTableSchema('{{%product}}', true)->getColumn('delivery_days') !== null) {
            $this->dropColumn('{{%product}}', 'delivery_days');
        }
    }
}
