<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Monthly care plans (product.kind = plan): the maintenance service a paid plan order started, so My Purchases and
 * Store Sales can show its renewals. MySQL can't roll back table changes: each step checks what a failed run made.
 */
final class m261216_100000_care_plans extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%product_order}}', true)->getColumn('maintenance_id') === null) {
            $this->addColumn('{{%product_order}}', 'maintenance_id', $this->integer()->null()->after('support_ticket_id'));
        }
        if (!isset($this->db->getTableSchema('{{%product_order}}', true)->foreignKeys['fk-product_order-maintenance'])) {
            $this->addForeignKey('fk-product_order-maintenance', '{{%product_order}}', 'maintenance_id', '{{%maintenance}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if (isset($this->db->getTableSchema('{{%product_order}}', true)->foreignKeys['fk-product_order-maintenance'])) {
            $this->dropForeignKey('fk-product_order-maintenance', '{{%product_order}}');
        }
        if ($this->db->getTableSchema('{{%product_order}}', true)->getColumn('maintenance_id') !== null) {
            $this->dropColumn('{{%product_order}}', 'maintenance_id');
        }
    }
}
