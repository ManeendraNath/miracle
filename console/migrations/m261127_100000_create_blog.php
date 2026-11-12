<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * The public blog at /blog: categories and posts. A post shows on the site once Published and its publish time has
 * passed. Can be run again after a failure.
 */
final class m261127_100000_create_blog extends Migration
{
    public function safeUp(): void
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;
        if ($this->db->getTableSchema('{{%blog_category}}', true) === null) {
            $this->createTable('{{%blog_category}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'slug' => $this->string(120)->notNull()->unique(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $options);
        }
        if ($this->db->getTableSchema('{{%blog_post}}', true) === null) {
            $this->createTable('{{%blog_post}}', [
                'id' => $this->primaryKey(),
                'category_id' => $this->integer()->null(),
                'title' => $this->string(200)->notNull(),
                'slug' => $this->string(220)->notNull()->unique(),
                'summary' => $this->string(300)->notNull(),
                'body' => 'MEDIUMTEXT NOT NULL',
                'cover_image' => $this->string(255)->null(),
                'seo_title' => $this->string(70)->null(),
                'status' => $this->tinyInteger()->notNull()->defaultValue(0),
                'published_at' => $this->integer()->null(),
                'author_id' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $options);
            $this->createIndex('idx-blog_post-status-published_at', '{{%blog_post}}', ['status', 'published_at']);
            $this->addForeignKey('fk-blog_post-category', '{{%blog_post}}', 'category_id', '{{%blog_category}}', 'id', 'SET NULL', 'CASCADE');
            $this->addForeignKey('fk-blog_post-author', '{{%blog_post}}', 'author_id', '{{%admin}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%blog_post}}', true) !== null) {
            $this->dropTable('{{%blog_post}}');
        }
        if ($this->db->getTableSchema('{{%blog_category}}', true) !== null) {
            $this->dropTable('{{%blog_category}}');
        }
    }
}
