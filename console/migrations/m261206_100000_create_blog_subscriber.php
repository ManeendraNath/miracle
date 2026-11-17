<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Blog, stage 4: a newsletter. Readers subscribe with their email and confirm it (double opt-in); each new post is
 * emailed to confirmed subscribers by a cron job, in small batches. blog_newsletter_delivery records who got which
 * post, so a batch cut short carries on where it stopped.
 * Posts already published are marked as sent, so turning this on doesn't email the whole archive.
 * MySQL can't roll back table changes: each step checks what an earlier, failed run already made.
 */
final class m261206_100000_create_blog_subscriber extends Migration
{
    public function safeUp(): void
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;

        $post = $this->db->getTableSchema('{{%blog_post}}', true);
        if ($post->getColumn('send_newsletter') === null) {
            $this->addColumn('{{%blog_post}}', 'send_newsletter', $this->boolean()->notNull()->defaultValue(1)->after('comments_open'));
        }
        if ($post->getColumn('newsletter_started_at') === null) {
            $this->addColumn('{{%blog_post}}', 'newsletter_started_at', $this->integer()->null()->after('send_newsletter'));
        }
        if ($post->getColumn('newsletter_done_at') === null) {
            $this->addColumn('{{%blog_post}}', 'newsletter_done_at', $this->integer()->null()->after('newsletter_started_at'));
        }
        $this->update('{{%blog_post}}', ['newsletter_started_at' => time(), 'newsletter_done_at' => time()], ['and', ['newsletter_started_at' => null, 'status' => 1], ['<=', 'published_at', time()]]);
        // These addresses now belong to the newsletter pages; a post already using one would become unreachable.
        foreach (['subscribe', 'confirm', 'unsubscribe'] as $slug) {
            // With the post id: "subscribe-post" could already be taken.
            $this->update('{{%blog_post}}', ['slug' => new \yii\db\Expression("CONCAT(:slug, '-', id)", [':slug' => $slug])], ['slug' => $slug]);
        }

        if ($this->db->getTableSchema('{{%blog_subscriber}}', true) === null) {
            $this->createTable('{{%blog_subscriber}}', [
                'id' => $this->primaryKey(),
                'email' => $this->string(255)->notNull()->unique(),
                // 0 waiting for confirmation, 1 subscribed, 2 unsubscribed
                'status' => $this->tinyInteger()->notNull()->defaultValue(0),
                // In every confirmation and newsletter link: confirms and unsubscribes without signing in.
                // Matched exactly (a secret): binary, not the table's case-insensitive collation.
                'token' => $this->db->driverName === 'mysql' ? 'VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE' : $this->string(64)->notNull()->unique(),
                'ip' => $this->string(45)->null(),
                // The last confirmation email: another goes at most once a day, so the form can't be used to flood an inbox.
                'confirm_sent_at' => $this->integer()->null(),
                'confirmed_at' => $this->integer()->null(),
                'unsubscribed_at' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], $options);
        }
        if ($this->db->getTableSchema('{{%blog_newsletter_delivery}}', true) === null) {
            $this->createTable('{{%blog_newsletter_delivery}}', [
                'post_id' => $this->integer()->notNull(),
                'subscriber_id' => $this->integer()->notNull(),
                'sent_at' => $this->integer()->notNull(),
                // The mail server refused this address: not tried again for this post.
                'failed' => $this->boolean()->notNull()->defaultValue(0),
                'PRIMARY KEY (post_id, subscriber_id)',
            ], $options);
        }
        $delivery = $this->db->getTableSchema('{{%blog_newsletter_delivery}}', true);
        if (!isset($delivery->foreignKeys['fk-blog_newsletter_delivery-post'])) {
            $this->addForeignKey('fk-blog_newsletter_delivery-post', '{{%blog_newsletter_delivery}}', 'post_id', '{{%blog_post}}', 'id', 'CASCADE', 'CASCADE');
        }
        if (!isset($delivery->foreignKeys['fk-blog_newsletter_delivery-subscriber'])) {
            $this->addForeignKey('fk-blog_newsletter_delivery-subscriber', '{{%blog_newsletter_delivery}}', 'subscriber_id', '{{%blog_subscriber}}', 'id', 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        foreach (['blog_newsletter_delivery', 'blog_subscriber'] as $table) {
            if ($this->db->getTableSchema('{{%' . $table . '}}', true) !== null) {
                $this->dropTable('{{%' . $table . '}}');
            }
        }
        foreach (['newsletter_done_at', 'newsletter_started_at', 'send_newsletter'] as $column) {
            if ($this->db->getTableSchema('{{%blog_post}}', true)->getColumn($column) !== null) {
                $this->dropColumn('{{%blog_post}}', $column);
            }
        }
    }
}
