<?php

declare(strict_types=1);

use common\components\CustomYii;
use common\models\BlogSubscriber;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\base\Model $model */

$this->title = 'Subscribe to the blog | ' . (CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$this->params['meta_description'] = 'Get new posts from the blog by email.';
$this->params['robots'] = 'noindex, follow';
$notice = Yii::$app->session->getFlash('subscribe');
?>
<section class="mw-section">
    <div class="container" style="max-width: 40rem">
        <h1 class="mw-h1 mb-4">Subscribe to the blog</h1>
        <?php if (is_array($notice)): ?>
            <div class="alert alert-<?= $notice[0] === 'success' ? 'success' : 'danger' ?>" role="<?= $notice[0] === 'success' ? 'status' : 'alert' ?>"><?= Html::encode($notice[1]) ?></div>
        <?php endif; ?>
        <?= $this->render('_subscribe', ['model' => $model, 'id' => 'subscribe-page', 'main' => true]) ?>
        <p class="small text-muted mt-3">We keep your email address, when you subscribed and confirmed, and your IP address, only to send new posts. Sign-ups not confirmed within <?= BlogSubscriber::UNCONFIRMED_DAYS ?> days are deleted. See the <?= Html::a('privacy policy', ['/site/privacy']) ?>.</p>
        <p class="mt-4"><?= Html::a('← Back to the blog', ['/blog/index']) ?></p>
    </div>
</section>
