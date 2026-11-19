<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Store, stage 2: buying. A customer's order of one licence of a product, paid through its own invoice (UPI or bank
 * transfer always, Razorpay or PayPal when on). When the invoice is paid the order gets a licence key and its file
 * can be downloaded. The product's file is kept outside the web root.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261211_100000_create_product_order extends Migration
{
    public function safeUp(): void
    {
        $product = $this->db->getTableSchema('{{%product}}', true);
        if ($product !== null && !isset($product->columns['download_file'])) {
            // Stored file name under @storage/store-files.
            $this->addColumn('{{%product}}', 'download_file', $this->string(255)->null()->after('version'));
            // The name buyers download it as.
            $this->addColumn('{{%product}}', 'download_name', $this->string(255)->null()->after('download_file'));
        }
        if ($this->db->getTableSchema('{{%product_order}}', true) === null) {
            $this->createTable('{{%product_order}}', [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer()->notNull(),
                'product_id' => $this->integer()->notNull(),
                // single or unlimited
                'licence' => $this->string(10)->notNull(),
                'invoice_id' => $this->integer()->notNull()->unique(),
                // Given when paid.
                'licence_key' => $this->string(32)->null()->unique(),
                'paid_at' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null);
        }
        $keys = $this->db->getTableSchema('{{%product_order}}', true)->foreignKeys;
        if (!isset($keys['fk-product_order-user'])) {
            $this->addForeignKey('fk-product_order-user', '{{%product_order}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');
        }
        // A product that was sold can't be deleted: staff set it to Draft instead.
        if (!isset($keys['fk-product_order-product'])) {
            $this->addForeignKey('fk-product_order-product', '{{%product_order}}', 'product_id', '{{%product}}', 'id', 'RESTRICT', 'CASCADE');
        }
        if (!isset($keys['fk-product_order-invoice'])) {
            $this->addForeignKey('fk-product_order-invoice', '{{%product_order}}', 'invoice_id', '{{%invoice}}', 'id', 'RESTRICT', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%product_order}}', true) !== null) {
            $this->dropTable('{{%product_order}}');
        }
        if (isset($this->db->getTableSchema('{{%product}}', true)->columns['download_file'])) {
            $this->dropColumn('{{%product}}', 'download_name');
            $this->dropColumn('{{%product}}', 'download_file');
        }
    }
}
