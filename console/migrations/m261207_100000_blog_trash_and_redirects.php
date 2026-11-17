<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Blog, stage 5: a trash for posts (deleted_at: off the website, restorable, emptied after 30 days by the weekly
 * cleanup) and old web addresses of posts whose address changed, so links to them keep working (301 redirect).
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261207_100000_blog_trash_and_redirects extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%blog_post}}', true)->getColumn('deleted_at') === null) {
            $this->addColumn('{{%blog_post}}', 'deleted_at', $this->integer()->null()->after('newsletter_done_at'));
        }
        if ($this->db->getTableSchema('{{%blog_post_redirect}}', true) === null) {
            $this->createTable('{{%blog_post_redirect}}', [
                'id' => $this->primaryKey(),
                'old_slug' => $this->string(255)->notNull()->unique(),
                'post_id' => $this->integer()->notNull(),
                'created_at' => $this->integer()->notNull(),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null);
        }
        if (!isset($this->db->getTableSchema('{{%blog_post_redirect}}', true)->foreignKeys['fk-blog_post_redirect-post'])) {
            $this->addForeignKey('fk-blog_post_redirect-post', '{{%blog_post_redirect}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%blog_post_redirect}}', true) !== null) {
            $this->dropTable('{{%blog_post_redirect}}');
        }
        if ($this->db->getTableSchema('{{%blog_post}}', true)->getColumn('deleted_at') !== null) {
            $this->dropColumn('{{%blog_post}}', 'deleted_at');
        }
    }
}
