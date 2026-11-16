<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Cover images for the first four blog posts (m261129_100000_add_first_blog_posts), from blog-covers/ next to this
 * file, copied into the blog upload folder that deploys keep. A post that already has a cover keeps it.
 * Can be run again.
 */
final class m261201_100000_add_blog_covers extends Migration
{
    private const DIR = 'uploads/blog/covers';

    public function safeUp(): void
    {
        foreach (glob(__DIR__ . '/blog-covers/*.jpg') ?: [] as $source) {
            $slug = basename($source, '.jpg');
            $post = $this->db->createCommand('SELECT id, cover_image FROM {{%blog_post}} WHERE slug = :slug', [':slug' => $slug])->queryOne();
            if ($post === false || (string) $post['cover_image'] !== '') {
                continue;
            }
            $relative = self::DIR . '/' . $slug . '.jpg';
            $target = Yii::getAlias('@frontend/web/' . $relative);
            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true) && !is_dir(dirname($target))) {
                throw new RuntimeException('Cannot create ' . dirname($target));
            }
            if (!copy($source, $target)) {
                throw new RuntimeException('Cannot copy the cover to ' . $target);
            }
            $this->update('{{%blog_post}}', ['cover_image' => $relative], ['id' => (int) $post['id']]);
        }
    }

    public function safeDown(): void
    {
        foreach (glob(__DIR__ . '/blog-covers/*.jpg') ?: [] as $source) {
            $relative = self::DIR . '/' . basename($source);
            // Only covers this migration set; one chosen later by staff stays.
            $this->update('{{%blog_post}}', ['cover_image' => null], ['cover_image' => $relative]);
            @unlink(Yii::getAlias('@frontend/web/' . $relative));
        }
    }
}
