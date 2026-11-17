<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $post */
/** @var string $postUrl */
/** @var string|null $coverUrl */
/** @var string $unsubscribeUrl */
/** @var string $site */
?>
<?php if ($coverUrl !== null): ?>
    <a href="<?= Html::encode($postUrl) ?>"><img src="<?= Html::encode($coverUrl) ?>" alt="<?= Html::encode((string) $post->cover_alt) ?>" width="620" style="display:block; width:100%; max-width:620px; height:auto; border-radius:14px; margin-bottom:18px;"></a>
<?php endif; ?>
<?= $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => 'New on the blog',
    'title' => $post->title,
    'intro' => $post->summary,
    'button' => ['Read the post', $postUrl],
]) ?>
<p style="margin:18px 0 0; font-size:12px; line-height:1.6; color:#6b7280; text-align:center;">
    You get this because you subscribed to the <?= Html::encode($site) ?> blog.
    <a href="<?= Html::encode($unsubscribeUrl) ?>" style="color:#6b7280;">Unsubscribe</a>
</p>
