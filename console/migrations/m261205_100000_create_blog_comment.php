<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Blog, stage 3: reader comments, shown only once staff approve them, and staff replies. A post can have its
 * comments closed. MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261205_100000_create_blog_comment extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%blog_post}}', true)->getColumn('comments_open') === null) {
            $this->addColumn('{{%blog_post}}', 'comments_open', $this->boolean()->notNull()->defaultValue(1)->after('noindex'));
        }
        if ($this->db->getTableSchema('{{%blog_comment}}', true) === null) {
            $this->createTable('{{%blog_comment}}', [
                'id' => $this->primaryKey(),
                'post_id' => $this->integer()->notNull(),
                // A staff reply points at the comment it answers; reader comments have none.
                'parent_id' => $this->integer()->null(),
                'admin_id' => $this->integer()->null(),
                'author_name' => $this->string(100)->notNull(),
                'author_email' => $this->string(255)->null(),
                'body' => $this->text()->notNull(),
                'status' => $this->tinyInteger()->notNull()->defaultValue(0),
                'ip' => $this->string(45)->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null);
        }
        $indexes = array_column($this->db->createCommand('SHOW INDEX FROM {{%blog_comment}}')->queryAll(), 'Key_name');
        if (!in_array('idx-blog_comment-post-status', $indexes, true)) {
            $this->createIndex('idx-blog_comment-post-status', '{{%blog_comment}}', ['post_id', 'status']);
        }
        if (!in_array('idx-blog_comment-ip-created', $indexes, true)) {
            $this->createIndex('idx-blog_comment-ip-created', '{{%blog_comment}}', ['ip', 'created_at']);
        }
        $comment = $this->db->getTableSchema('{{%blog_comment}}', true);
        if (!isset($comment->foreignKeys['fk-blog_comment-post'])) {
            $this->addForeignKey('fk-blog_comment-post', '{{%blog_comment}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
        if (!isset($comment->foreignKeys['fk-blog_comment-parent'])) {
            $this->addForeignKey('fk-blog_comment-parent', '{{%blog_comment}}', 'parent_id', '{{%blog_comment}}', 'id', 'CASCADE', 'CASCADE');
        }
        if (!isset($comment->foreignKeys['fk-blog_comment-admin'])) {
            $this->addForeignKey('fk-blog_comment-admin', '{{%blog_comment}}', 'admin_id', '{{%admin}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%blog_comment}}', true) !== null) {
            $this->dropTable('{{%blog_comment}}');
        }
        if ($this->db->getTableSchema('{{%blog_post}}', true)->getColumn('comments_open') !== null) {
            $this->dropColumn('{{%blog_post}}', 'comments_open');
        }
    }
}
