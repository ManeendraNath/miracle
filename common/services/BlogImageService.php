<?php

declare(strict_types=1);

namespace common\services;

use common\models\BlogPost;
use Yii;
use yii\web\UploadedFile;

/**
 * Saves a blog image under the frontend web root, scaled down to at most 1600px wide and re-encoded,
 * which also drops anything hidden in the file (camera data, scripts in disguise).
 */
final class BlogImageService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_WIDTH = 1600;
    private const MAX_PIXELS = 40_000_000;
    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    /**
     * @return string the path under the frontend web root, e.g. uploads/blog/2026-11/1f3a9c.jpg
     * @throws \DomainException with a message for staff when the file is not a usable image
     */
    public function store(UploadedFile $file): string
    {
        if ($file->error !== UPLOAD_ERR_OK) {
            throw new \DomainException('The image could not be uploaded. Please try again.');
        }
        if ($file->size > self::MAX_BYTES) {
            throw new \DomainException('The image is larger than 5 MB.');
        }
        $info = @getimagesize($file->tempName);
        $type = $info[2] ?? null;
        if (!isset(self::TYPES[$type])) {
            throw new \DomainException('Use a JPG, PNG or WebP image.');
        }
        // A small file can still unpack to a huge picture: about 5 bytes a pixel, twice over while turning or resizing.
        if ($info[0] * $info[1] > self::MAX_PIXELS || !self::makeRoom(memory_get_usage() + $info[0] * $info[1] * 10)) {
            throw new \DomainException('The image is too large to resize. Use one under 12 megapixels, e.g. 4000 x 3000.');
        }
        $image = @imagecreatefromstring((string) file_get_contents($file->tempName));
        if ($image === false) {
            throw new \DomainException('The image could not be read.');
        }
        if ($type === IMAGETYPE_JPEG) {
            $image = self::upright($image, $file->tempName);
        }
        if (imagesx($image) > self::MAX_WIDTH) {
            $scaled = imagescale($image, self::MAX_WIDTH);
            imagedestroy($image);
            $image = $scaled;
        }
        $extension = self::TYPES[$type];
        $relative = BlogPost::UPLOAD_DIR . '/' . date('Y-m') . '/' . bin2hex(random_bytes(8)) . '.' . $extension;
        $path = Yii::getAlias('@frontend/web/' . $relative);
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0775, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('Cannot create the blog image folder.');
        }
        if ($type !== IMAGETYPE_JPEG) {
            imagesavealpha($image, true);
        }
        $saved = match ($type) {
            IMAGETYPE_PNG => imagepng($image, $path, 6),
            IMAGETYPE_WEBP => imagewebp($image, $path, 82),
            default => imagejpeg($image, $path, 82),
        };
        imagedestroy($image);
        if (!$saved) {
            throw new \RuntimeException('Cannot save the blog image.');
        }

        return $relative;
    }

    /**
     * Phone photos are stored sideways with a note (EXIF Orientation) saying how to turn them; re-encoding drops
     * the note, so the turn is applied here.
     * ponytail: the mirrored orientations (2, 4, 5, 7) are left as they are; cameras almost never write them.
     */
    private static function upright(\GdImage $image, string $path): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $angle = match (is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }
        $turned = imagerotate($image, $angle, 0);
        if ($turned === false) {
            return $image;
        }
        imagedestroy($image);

        return $turned;
    }

    /** True when PHP may use $bytes, raising this request's memory limit if it has to and the server allows it. */
    private static function makeRoom(int $bytes): bool
    {
        // Quiet: an odd server value or a host cap must give the friendly message, not an error page.
        $fits = static function () use ($bytes): bool {
            $limit = (string) ini_get('memory_limit');

            return $limit === '-1' || @ini_parse_quantity($limit) >= $bytes;
        };
        if ($fits()) {
            return true;
        }
        @ini_set('memory_limit', (string) $bytes);

        return $fits();
    }
}
