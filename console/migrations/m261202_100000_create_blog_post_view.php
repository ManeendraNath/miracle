<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Blog post views: one row per visitor per post per day. The visitor is a hash that changes daily, so it can't be
 * traced back to an address or followed from one day to the next. Can be run again after a failure.
 */
final class m261202_100000_create_blog_post_view extends Migration
{
    public function safeUp(): void
    {
        // MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
        if ($this->db->getTableSchema('{{%blog_post_view}}', true) === null) {
            $this->createTable('{{%blog_post_view}}', [
                'id' => $this->primaryKey(),
                'post_id' => $this->integer()->notNull(),
                'day' => $this->date()->notNull(),
                'visitor' => $this->char(32)->notNull(),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null);
        }
        $table = $this->db->quoteTableName('{{%blog_post_view}}');
        if ($this->db->createCommand("SHOW INDEX FROM $table WHERE Key_name = 'ux-blog_post_view-post-day-visitor'")->queryOne() === false) {
            $this->createIndex('ux-blog_post_view-post-day-visitor', '{{%blog_post_view}}', ['post_id', 'day', 'visitor'], true);
        }
        if (!isset($this->db->getTableSchema('{{%blog_post_view}}', true)->foreignKeys['fk-blog_post_view-post'])) {
            $this->addForeignKey('fk-blog_post_view-post', '{{%blog_post_view}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%blog_post_view}}', true) !== null) {
            $this->dropTable('{{%blog_post_view}}');
        }
    }
}
