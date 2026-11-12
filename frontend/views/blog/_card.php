<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\BlogPost $post */

$link = Url::to(['/blog/view', 'slug' => $post->slug]);
?>
<article class="mw-card mw-project mw-post-card">
    <?php if ($post->cover_image): ?>
        <a href="<?= Html::encode($link) ?>" tabindex="-1" aria-hidden="true"><?= Html::img('@web/' . $post->cover_image, ['alt' => '', 'loading' => 'lazy', 'class' => 'mw-post-cover']) ?></a>
    <?php endif; ?>
    <div class="mw-project-body">
        <p class="mw-eyebrow mb-1"><?= Html::encode($post->category->name ?? 'Article') ?></p>
        <h3><a href="<?= Html::encode($link) ?>" class="stretched-link text-reset text-decoration-none"><?= Html::encode($post->title) ?></a></h3>
        <p><?= Html::encode($post->summary) ?></p>
        <p class="small mb-0 text-muted"><?= Yii::$app->formatter->asDate($post->published_at, 'php:d M Y') ?> · <?= $post->readingMinutes() ?> min read</p>
    </div>
</article>
