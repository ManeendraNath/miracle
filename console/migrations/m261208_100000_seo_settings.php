<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SEO: search engine verification codes and the IndexNow key in Site Settings, and seo_page, where staff can set the
 * title and description search engines show for each fixed page of the website (home, about, contact...).
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261208_100000_seo_settings extends Migration
{
    public function safeUp(): void
    {
        $settings = $this->db->getTableSchema('{{%site_setting}}', true);
        foreach (['google_site_verification' => 100, 'bing_site_verification' => 100, 'indexnow_key' => 64] as $column => $length) {
            if ($settings->getColumn($column) === null) {
                $this->addColumn('{{%site_setting}}', $column, $this->string($length)->null());
            }
        }
        // IndexNow key: made once here, so two first uses can't each make their own.
        $this->update('{{%site_setting}}', ['indexnow_key' => bin2hex(random_bytes(16))], ['indexnow_key' => null]);
        if ($this->db->getTableSchema('{{%seo_page}}', true) === null) {
            $this->createTable('{{%seo_page}}', [
                'id' => $this->primaryKey(),
                // The page's route, e.g. site/about.
                'route' => $this->string(64)->notNull()->unique(),
                'title' => $this->string(70)->null(),
                'description' => $this->string(170)->null(),
                'updated_at' => $this->integer()->notNull(),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null);
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%seo_page}}', true) !== null) {
            $this->dropTable('{{%seo_page}}');
        }
        foreach (['indexnow_key', 'bing_site_verification', 'google_site_verification'] as $column) {
            if ($this->db->getTableSchema('{{%site_setting}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%site_setting}}', $column);
            }
        }
    }
}
