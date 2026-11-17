<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Blog, stage 1: tags, saved earlier versions of a post (revisions), cover alt text, a featured post, a "keep out of
 * search engines" switch, and author profiles (name shown, bio, photo) for staff who write.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261204_100000_blog_tags_revisions_authors extends Migration
{
    public function safeUp(): void
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;

        $post = $this->db->getTableSchema('{{%blog_post}}', true);
        if ($post->getColumn('cover_alt') === null) {
            $this->addColumn('{{%blog_post}}', 'cover_alt', $this->string(200)->null()->after('cover_image'));
        }
        if ($post->getColumn('is_featured') === null) {
            $this->addColumn('{{%blog_post}}', 'is_featured', $this->boolean()->notNull()->defaultValue(0)->after('status'));
        }
        if ($post->getColumn('noindex') === null) {
            $this->addColumn('{{%blog_post}}', 'noindex', $this->boolean()->notNull()->defaultValue(0)->after('is_featured'));
        }

        $admin = $this->db->getTableSchema('{{%admin}}', true);
        if ($admin->getColumn('display_name') === null) {
            $this->addColumn('{{%admin}}', 'display_name', $this->string(100)->null()->after('email'));
        }
        if ($admin->getColumn('bio') === null) {
            $this->addColumn('{{%admin}}', 'bio', $this->string(500)->null()->after('display_name'));
        }
        if ($admin->getColumn('photo') === null) {
            $this->addColumn('{{%admin}}', 'photo', $this->string(255)->null()->after('bio'));
        }

        if ($this->db->getTableSchema('{{%blog_tag}}', true) === null) {
            $this->createTable('{{%blog_tag}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(60)->notNull(),
                'slug' => $this->string(80)->notNull()->unique(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $options);
        }
        if ($this->db->getTableSchema('{{%blog_post_tag}}', true) === null) {
            $this->createTable('{{%blog_post_tag}}', [
                'post_id' => $this->integer()->notNull(),
                'tag_id' => $this->integer()->notNull(),
                'PRIMARY KEY (post_id, tag_id)',
            ], $options);
        }
        $postTag = $this->db->getTableSchema('{{%blog_post_tag}}', true);
        if (!isset($postTag->foreignKeys['fk-blog_post_tag-post'])) {
            $this->addForeignKey('fk-blog_post_tag-post', '{{%blog_post_tag}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
        if (!isset($postTag->foreignKeys['fk-blog_post_tag-tag'])) {
            $this->addForeignKey('fk-blog_post_tag-tag', '{{%blog_post_tag}}', 'tag_id', '{{%blog_tag}}', 'id', 'CASCADE', 'CASCADE');
        }

        if ($this->db->getTableSchema('{{%blog_post_revision}}', true) === null) {
            $this->createTable('{{%blog_post_revision}}', [
                'id' => $this->primaryKey(),
                'post_id' => $this->integer()->notNull(),
                'title' => $this->string(200)->notNull(),
                'summary' => $this->string(300)->notNull(),
                'body' => $this->getDb()->getSchema()->createColumnSchemaBuilder('mediumtext')->notNull(),
                'author_id' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
            ], $options);
        }
        if (!isset($this->db->getTableSchema('{{%blog_post_revision}}', true)->foreignKeys['fk-blog_post_revision-post'])) {
            $this->addForeignKey('fk-blog_post_revision-post', '{{%blog_post_revision}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
        if (!isset($this->db->getTableSchema('{{%blog_post_revision}}', true)->foreignKeys['fk-blog_post_revision-author'])) {
            $this->addForeignKey('fk-blog_post_revision-author', '{{%blog_post_revision}}', 'author_id', '{{%admin}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        foreach (['blog_post_revision', 'blog_post_tag', 'blog_tag'] as $table) {
            if ($this->db->getTableSchema('{{%' . $table . '}}', true) !== null) {
                $this->dropTable('{{%' . $table . '}}');
            }
        }
        foreach (['admin' => ['photo', 'bio', 'display_name'], 'blog_post' => ['noindex', 'is_featured', 'cover_alt']] as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->db->getTableSchema('{{%' . $table . '}}', true)->getColumn($column) !== null) {
                    $this->dropColumn('{{%' . $table . '}}', $column);
                }
            }
        }
    }
}
