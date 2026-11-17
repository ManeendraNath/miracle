<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogPostRevision $revision */
/** @var common\models\BlogPost $model */

$when = Yii::$app->formatter->asDatetime($revision->created_at, 'php:d M Y, H:i');
$this->title = 'Earlier version: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Blog Posts', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Version of ' . $when;
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left me-1"></i> Back to the post', ['update', 'id' => $model->id], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-undo me-1"></i> Restore this version', ['restore', 'id' => $revision->id], ['class' => 'btn btn-primary', 'data' => ['method' => 'post', 'confirm' => 'Put this version\'s title, summary and text back? The current text is kept in the version list.']]),
];
$this->params['help'] = [
    'Earlier version' => 'The title, summary and text as they were until ' . $when . '. Cover, tags and settings are not part of a version and stay as they are.',
];
?>
<div class="card shadow-sm">
    <div class="card-body blog-preview mx-auto py-5">
        <span class="badge bg-secondary mb-3">Version of <?= Html::encode($when) ?></span>
        <h1 class="fw-bold mb-2"><?= Html::encode($revision->title) ?></h1>
        <p class="lead text-muted"><?= Html::encode($revision->summary) ?></p>
        <div class="mt-4"><?= $revision->body /* saved from a cleaned post (BlogPost::cleanBody) */ ?></div>
    </div>
</div>
