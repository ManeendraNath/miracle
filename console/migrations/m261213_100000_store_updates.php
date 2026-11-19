<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Store, stage 3: what's new in each version of a product (buyers get every new version free), and when a buyer first
 * downloaded their purchase (refunds are given in full until then: see the licence terms).
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261213_100000_store_updates extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%product}}', true)->getColumn('changelog') === null) {
            // Newest version first, as staff write it.
            $this->addColumn('{{%product}}', 'changelog', $this->text()->null()->after('download_name'));
        }
        if ($this->db->getTableSchema('{{%product_order}}', true)->getColumn('first_downloaded_at') === null) {
            $this->addColumn('{{%product_order}}', 'first_downloaded_at', $this->integer()->null()->after('paid_at'));
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%product_order}}', true)->getColumn('first_downloaded_at') !== null) {
            $this->dropColumn('{{%product_order}}', 'first_downloaded_at');
        }
        if ($this->db->getTableSchema('{{%product}}', true)->getColumn('changelog') !== null) {
            $this->dropColumn('{{%product}}', 'changelog');
        }
    }
}
