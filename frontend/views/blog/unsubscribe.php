<?php

declare(strict_types=1);

use common\components\CustomYii;
use common\models\BlogSubscriber;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var BlogSubscriber $subscriber */

$this->title = 'Unsubscribe | ' . (CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$this->params['robots'] = 'noindex, nofollow';
?>
<section class="mw-section">
    <div class="container" style="max-width: 40rem">
        <h1 class="mw-h1 mb-3">Unsubscribe from the blog</h1>
        <?php if ($subscriber->status === BlogSubscriber::STATUS_UNSUBSCRIBED): ?>
            <p class="mw-lead"><?= Html::encode($subscriber->email) ?> is already unsubscribed and gets no emails from the blog.</p>
        <?php else: ?>
            <p class="mw-lead">Stop sending new blog posts to <?= Html::encode($subscriber->email) ?>?</p>
            <form action="<?= Html::encode(Url::to(['/blog/unsubscribe', 'token' => $subscriber->token])) ?>" method="post">
                <button type="submit" class="btn btn-primary">Unsubscribe</button>
            </form>
        <?php endif; ?>
    </div>
</section>
