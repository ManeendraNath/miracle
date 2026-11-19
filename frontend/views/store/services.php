<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$this->title = 'Fixed-price website services | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = 'Website jobs at a fixed price from ' . $siteName . ': pay online, tell us what you need, and get it done in a set number of working days.';
$services = $dataProvider->getModels();
$page = $dataProvider->getPagination() ? $dataProvider->getPagination()->getPage() + 1 : 1;
$this->params['canonical'] = Url::to(['/store/services'] + ($page > 1 ? ['page' => $page] : []), true);
?>
<div class="store-services">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <p class="mw-eyebrow mb-2">Fixed-price services</p>
            <h1 class="mw-h1 mb-3">Website jobs at a fixed price</h1>
            <p class="mw-lead mb-0">Choose the job, tell us what you need, and pay. We start once the payment is confirmed, and you follow the work in your account.</p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <?php if ($services === []): ?>
                <p class="mw-lead">No fixed-price services are listed yet. <?= Html::a('Tell us what you need', ['/site/contact', 'subject' => 'Website job']) ?> and we will quote for it.</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $service): ?>
                        <div class="col-md-6 col-lg-4"><?= $this->render('_card', ['model' => $service]) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4"><?= LinkPager::widget(['pagination' => $dataProvider->pagination, 'options' => ['class' => 'pagination'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?></div>
                <p class="mt-4 mb-0">Need something not listed? <?= Html::a('Ask for a quote', ['/site/contact', 'subject' => 'Website job']) ?>.</p>
            <?php endif; ?>
        </div>
    </section>
</div>
