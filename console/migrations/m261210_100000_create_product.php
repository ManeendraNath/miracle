<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Store, stage 1: the catalogue of website themes and plugins sold from the site (HTML and WordPress themes,
 * WordPress and Yii2 plugins), each with screenshots, a live demo, and prices for a single-site and an
 * unlimited-sites licence in rupees and US dollars. Buying comes in stage 2.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261210_100000_create_product extends Migration
{
    public function safeUp(): void
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;
        if ($this->db->getTableSchema('{{%product}}', true) === null) {
            $this->createTable('{{%product}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(150)->notNull(),
                'slug' => $this->string(160)->notNull()->unique(),
                // html, wordpress or yii2
                'technology' => $this->string(20)->notNull(),
                // theme or plugin
                'kind' => $this->string(10)->notNull(),
                'summary' => $this->string(300)->notNull(),
                // Paragraphs of plain text (a blank line between them).
                'description' => $this->text()->notNull(),
                // One feature a line.
                'features' => $this->text()->null(),
                'cover_image' => $this->string(255)->null(),
                'demo_url' => $this->string(255)->null(),
                'version' => $this->string(20)->null(),
                'price_single_inr' => $this->decimal(10, 2)->notNull(),
                'price_single_usd' => $this->decimal(10, 2)->notNull(),
                // Empty: no unlimited-sites licence for this product.
                'price_unlimited_inr' => $this->decimal(10, 2)->null(),
                'price_unlimited_usd' => $this->decimal(10, 2)->null(),
                // 0 draft, 1 published
                'status' => $this->tinyInteger()->notNull()->defaultValue(0),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'seo_title' => $this->string(70)->null(),
                'seo_description' => $this->string(170)->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $options);
        }
        if ($this->db->getTableSchema('{{%product_image}}', true) === null) {
            $this->createTable('{{%product_image}}', [
                'id' => $this->primaryKey(),
                'product_id' => $this->integer()->notNull(),
                'path' => $this->string(255)->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
            ], $options);
        }
        if (!isset($this->db->getTableSchema('{{%product_image}}', true)->foreignKeys['fk-product_image-product'])) {
            $this->addForeignKey('fk-product_image-product', '{{%product_image}}', 'product_id', '{{%product}}', 'id', 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        foreach (['product_image', 'product'] as $table) {
            if ($this->db->getTableSchema('{{%' . $table . '}}', true) !== null) {
                $this->dropTable('{{%' . $table . '}}');
            }
        }
    }
}
