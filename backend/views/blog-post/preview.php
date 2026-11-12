<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $model */

$this->title = 'Preview: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Blog Posts', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Preview';
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-edit me-1"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']),
];
if ($model->isPublic()) {
    $this->params['actions'][] = Html::a('<i class="fas fa-external-link-alt me-1"></i> Open on the website', rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/blog/' . $model->slug, ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener']);
}
$this->params['help'] = [
    'Preview' => 'The text and images as the post will read on the website; the website adds its own header, related posts and a "Discuss your project" box.',
    'Status' => $model->isPublic() ? 'Live on the website.' : ($model->stateLabel() === 'Scheduled' ? 'Goes live on its own at the publish time.' : 'Draft: visitors can\'t see it until you publish it.'),
];
?>
<div class="card shadow-sm">
    <div class="card-body blog-preview mx-auto py-5">
        <span class="badge bg-secondary mb-3"><?= Html::encode($model->stateLabel()) ?></span>
        <h1 class="fw-bold mb-2"><?= Html::encode($model->title) ?></h1>
        <p class="lead text-muted"><?= Html::encode($model->summary) ?></p>
        <?php if ($model->cover_image): ?>
            <?= Html::img(rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/' . $model->cover_image, ['class' => 'img-fluid rounded my-3', 'alt' => '']) ?>
        <?php endif; ?>
        <div class="mt-4"><?= $model->body /* cleaned to article markup when saved (BlogPost::cleanBody) */ ?></div>
    </div>
</div>
