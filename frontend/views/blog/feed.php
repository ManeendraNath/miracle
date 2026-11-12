<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\BlogPost[] $posts */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?= Html::encode($siteName . ' Blog') ?></title>
    <link><?= Html::encode(Url::to(['/blog/index'], true)) ?></link>
    <atom:link href="<?= Html::encode(Url::to(['/blog/feed'], true)) ?>" rel="self" type="application/rss+xml"/>
    <description>Web development, e-commerce and hosting articles from <?= Html::encode($siteName) ?>.</description>
    <language>en</language>
<?php foreach ($posts as $post): ?>
    <item>
        <title><?= Html::encode($post->title) ?></title>
        <link><?= Html::encode(Url::to(['/blog/view', 'slug' => $post->slug], true)) ?></link>
        <guid isPermaLink="true"><?= Html::encode(Url::to(['/blog/view', 'slug' => $post->slug], true)) ?></guid>
        <pubDate><?= date(DATE_RSS, (int) $post->published_at) ?></pubDate>
        <description><?= Html::encode($post->summary) ?></description>
    </item>
<?php endforeach; ?>
</channel>
</rss>
