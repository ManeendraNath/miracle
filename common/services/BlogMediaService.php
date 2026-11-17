<?php

declare(strict_types=1);

namespace common\services;

use common\models\BlogPost;
use Yii;
use yii\db\Query;

/** The blog's uploaded images (covers and pictures in posts), and which posts use them. */
final class BlogMediaService
{
    /** Paths BlogImageService writes, e.g. uploads/blog/2026-11/1f3a9c0d2b4e6f70.jpg; nothing else is listed or deleted. */
    private const PATH = '#^' . BlogPost::UPLOAD_DIR . '/\d{4}-\d{2}/[a-f0-9]{16}\.(jpg|png|webp)$#';

    /** @return list<array{path: string, size: int, time: int, posts: list<array{id: int, title: string}>, photoOf: string|null, inRevision: bool}> newest first */
    public static function all(): array
    {
        $root = Yii::getAlias('@frontend/web/');
        $files = [];
        foreach (glob($root . BlogPost::UPLOAD_DIR . '/*/*.{jpg,png,webp}', GLOB_BRACE) ?: [] as $file) {
            $path = substr($file, strlen($root));
            if (preg_match(self::PATH, $path) === 1) {
                $files[] = ['path' => $path, 'size' => (int) filesize($file), 'time' => (int) filemtime($file), 'posts' => []];
            }
        }
        usort($files, static fn(array $a, array $b): int => $b['time'] <=> $a['time']);
        // ponytail: every post body read and searched per image; fine for hundreds of posts, an image table beyond.
        $posts = (new Query())->select(['id', 'title', 'cover_image', 'body'])->from('{{%blog_post}}')->all();
        $revisions = implode("\n", (new Query())->select('body')->from('{{%blog_post_revision}}')->column());
        // Author photos (My account) are stored here too.
        $photos = (new Query())->select(['photo', 'name' => 'COALESCE(display_name, username)'])->from('{{%admin}}')->where(['not', ['photo' => null]])->all();
        foreach ($files as &$file) {
            $file['photoOf'] = null;
            foreach ($photos as $photo) {
                if ($photo['photo'] === $file['path']) {
                    $file['photoOf'] = (string) $photo['name'];
                }
            }
            foreach ($posts as $post) {
                if ($post['cover_image'] === $file['path'] || str_contains((string) $post['body'], $file['path'])) {
                    $file['posts'][] = ['id' => (int) $post['id'], 'title' => (string) $post['title']];
                }
            }
            $file['inRevision'] = $file['posts'] === [] && $file['photoOf'] === null && str_contains($revisions, $file['path']);
        }
        unset($file);

        return $files;
    }

    /** Deletes an image no post uses. @throws \DomainException with a message for staff */
    public static function delete(string $path): void
    {
        if (preg_match(self::PATH, $path) !== 1) {
            throw new \DomainException('That is not a blog image.');
        }
        foreach (self::all() as $file) {
            if ($file['path'] === $path) {
                if ($file['posts'] !== []) {
                    throw new \DomainException('This image is used in a post. Remove it from the post first.');
                }
                if ($file['photoOf'] !== null) {
                    throw new \DomainException('This is the author photo of ' . $file['photoOf'] . '. Change it under My account.');
                }
                if (!@unlink(Yii::getAlias('@frontend/web/' . $path))) {
                    throw new \RuntimeException('The image could not be deleted.');
                }

                return;
            }
        }
        throw new \DomainException('That image no longer exists.');
    }
}
