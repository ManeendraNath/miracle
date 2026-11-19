<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $technology */
/** @var array<string, string> $technologies */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$heading = $technology !== '' ? $technologies[$technology] . ' themes & plugins' : 'Website themes & plugins';
$this->title = $heading . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = ($technology !== '' ? 'Ready-made ' . $technologies[$technology] . ' themes and plugins' : 'Ready-made website themes and plugins (HTML, WordPress and Yii2)')
    . ' from ' . $siteName . ', with setup help, 6 months of support, and single-site or unlimited-sites licences.';
// Products first: the page number is only checked against the page count once they are loaded.
$products = $dataProvider->getModels();
$page = $dataProvider->getPagination() ? $dataProvider->getPagination()->getPage() + 1 : 1;
// With one technology only, its filter shows the same as the whole store: one address for both.
$filtered = $technology !== '' && count($technologies) > 1;
$this->params['canonical'] = Url::to(['/store/index'] + ($filtered ? ['technology' => $technology] : []) + ($page > 1 ? ['page' => $page] : []), true);
?>
<div class="store-index">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <p class="mw-eyebrow mb-2">Store</p>
            <h1 class="mw-h1 mb-3"><?= Html::encode($heading) ?></h1>
            <p class="mw-lead mb-0">Ready-made themes and plugins from our team, with help to set them up and 6 months of support.</p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <?php if (count($technologies) > 1): ?>
                <nav class="mw-chips mb-5" aria-label="Filter by technology">
                    <?= Html::a('All', ['/store/index'], ['class' => 'mw-chip' . ($technology === '' ? ' active' : ''), 'aria-current' => $technology === '' ? 'page' : null]) ?>
                    <?php foreach ($technologies as $key => $label): ?>
                        <?= Html::a(Html::encode($label), ['/store/index', 'technology' => $key], ['class' => 'mw-chip' . ($technology === $key ? ' active' : ''), 'aria-current' => $technology === $key ? 'page' : null]) ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <?php if ($products === []): ?>
                <p class="mw-lead">No themes or plugins are listed yet. Looking for something? <?= Html::a('Tell us what you are looking for', ['/site/contact', 'subject' => 'Theme or plugin enquiry']) ?>.</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4"><?= $this->render('_card', ['model' => $product]) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4"><?= LinkPager::widget(['pagination' => $dataProvider->pagination, 'options' => ['class' => 'pagination'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?></div>
            <?php endif; ?>
        </div>
    </section>
</div>
