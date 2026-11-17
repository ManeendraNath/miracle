<?php

declare(strict_types=1);

use common\components\CustomYii;
use common\models\BlogSubscriber;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var BlogSubscriber $subscriber */

$this->title = 'Confirm your subscription | ' . (CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$this->params['robots'] = 'noindex, nofollow';
?>
<section class="mw-section">
    <div class="container" style="max-width: 40rem">
        <h1 class="mw-h1 mb-3">Confirm your subscription</h1>
        <?php if ($subscriber->status === BlogSubscriber::STATUS_UNSUBSCRIBED): ?>
            <p class="mw-lead">This link has expired: <?= Html::encode($subscriber->email) ?> unsubscribed after it was sent. To get new posts again, subscribe on the blog.</p>
            <p><?= Html::a('Subscribe again', ['/blog/subscribe'], ['class' => 'btn btn-primary']) ?></p>
        <?php elseif ($subscriber->status === BlogSubscriber::STATUS_SUBSCRIBED): ?>
            <p class="mw-lead"><?= Html::encode($subscriber->email) ?> is already subscribed.</p>
            <p><?= Html::a('Go to the blog', ['/blog/index'], ['class' => 'btn btn-primary']) ?></p>
        <?php else: ?>
            <p class="mw-lead">Send new blog posts to <?= Html::encode($subscriber->email) ?>?</p>
            <form action="<?= Html::encode(Url::to(['/blog/confirm', 'token' => $subscriber->token])) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <button type="submit" class="btn btn-primary">Yes, subscribe me</button>
            </form>
        <?php endif; ?>
    </div>
</section>
