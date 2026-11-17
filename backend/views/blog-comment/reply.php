<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogComment $comment */
/** @var common\models\BlogComment $reply */

$this->title = 'Reply to ' . $comment->author_name;
$this->params['breadcrumbs'][] = ['label' => 'Blog Comments', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Reply';
$this->params['help'] = [
    'Your reply' => 'Shown under the comment on the post with your author name from My account and a Team label. The comment is approved with it. The reader is not emailed.',
];
?>
<div class="card card-outline card-primary shadow-sm">
    <div class="card-body">
        <p class="text-muted small mb-1">On <?= Html::a(Html::encode($comment->post->title ?? ''), ['/blog-post/preview', 'id' => $comment->post_id], ['target' => '_blank', 'rel' => 'noopener']) ?>, <?= Yii::$app->formatter->asDatetime($comment->created_at, 'php:d M Y, H:i') ?></p>
        <p class="fw-bold mb-1"><?= Html::encode($comment->author_name) ?> wrote:</p>
        <blockquote class="border-start ps-3" style="overflow-wrap: anywhere"><?= nl2br(Html::encode($comment->body)) ?></blockquote>
        <?php foreach ($comment->replies as $earlier): ?>
            <p class="small border-start ps-3"><strong><?= Html::encode($earlier->author_name) ?>:</strong> <?= nl2br(Html::encode($earlier->body)) ?></p>
        <?php endforeach; ?>

        <?php $form = ActiveForm::begin(['id' => 'reply-form']); ?>
            <?= $form->field($reply, 'body')->label('Your reply')->textarea(['rows' => 6, 'maxlength' => 5000]) ?>
            <?= Html::submitButton('Post reply', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
