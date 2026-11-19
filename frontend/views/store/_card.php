<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\Product $model */
$link = Url::to(['/store/view', 'slug' => $model->slug]);
$fmt = Yii::$app->formatter;
?>
<article class="mw-card mw-project mw-post-card h-100">
    <?php $picture = $model->cover_image ?: ($model->images[0]->path ?? null); ?>
    <?php if ($picture): ?>
        <a href="<?= Html::encode($link) ?>" tabindex="-1" aria-hidden="true"><?= Html::img('@web/' . $picture, ['alt' => $model->name, 'loading' => 'lazy', 'class' => 'mw-post-cover']) ?></a>
    <?php endif; ?>
    <div class="mw-project-body">
        <p class="mw-eyebrow mb-1"><?= Html::encode($model->typeLabel()) ?></p>
        <h2 class="h5"><a href="<?= Html::encode($link) ?>" class="stretched-link text-reset text-decoration-none"><?= Html::encode($model->name) ?></a></h2>
        <p><?= Html::encode($model->summary) ?></p>
        <p class="fw-semibold mb-0"><?= $model->isService() ? '' : 'From ' ?>₹<?= $fmt->asDecimal($model->price_single_inr, 0) ?> <span class="text-body-secondary fw-normal">· US$<?= $fmt->asDecimal($model->price_single_usd, 2) ?><?= $model->isService() ? ' · ' . (int) $model->delivery_days . ' working days' : '' ?></span></p>
    </div>
</article>
