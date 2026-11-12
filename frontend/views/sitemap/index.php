<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var array<int, array{loc: string, lastmod?: string}> $urls */

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= Html::encode($url['loc']) ?></loc>
        <?= isset($url['lastmod']) ? '<lastmod>' . Html::encode($url['lastmod']) . "</lastmod>\n" : '' ?>
    </url>
<?php endforeach; ?>
</urlset>
