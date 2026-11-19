<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Product;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Themes & Plugins';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Store' => 'Website themes and plugins sold from the public store at /store. Only Published ones are shown there, and in the sitemap.',
    'Prices' => 'Each has a single-site licence and, optionally, an unlimited-sites licence, in rupees (clients in India) and US dollars (abroad).',
    'Buying' => 'Buy now creates an order and its invoice; once paid the buyer gets a licence key and the download on My Purchases. Orders are listed in Store Sales.',
    'Updates' => 'Buyers always get the latest file free. Upload a new one, set the version and What\'s new, and tick "Email buyers about this update".',
];
GridViewHelper::register($this);
$base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
?>
<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('Themes and plugins in the public store.', 'create', 'Add Product') ?>
    <div class="card-body p-0">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'layout' => GridPaginationHelper::gridLayout(),
            'emptyText' => 'No products yet. Add your first theme or plugin.',
            'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
            'columns' => [
                [
                    'attribute' => 'name',
                    'format' => 'raw',
                    'value' => static fn(Product $m): string => Html::encode($m->name) . '<div class="small text-body-secondary">/store/' . Html::encode($m->slug) . '</div>',
                ],
                ['label' => 'Type', 'value' => static fn(Product $m): string => $m->typeLabel()],
                [
                    'label' => 'Prices',
                    'format' => 'raw',
                    'value' => static fn(Product $m): string => 'Single: ₹' . Yii::$app->formatter->asDecimal($m->price_single_inr, 0) . ' / US$' . Yii::$app->formatter->asDecimal($m->price_single_usd, 2)
                        . ($m->hasUnlimited() ? '<br>Unlimited: ₹' . Yii::$app->formatter->asDecimal($m->price_unlimited_inr, 0) . ' / US$' . Yii::$app->formatter->asDecimal($m->price_unlimited_usd, 2) : ''),
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => static fn(Product $m): string => $m->isPublic()
                        ? Html::a('Published<span class="visually-hidden"> (opens in a new tab)</span>', $base . '/store/' . $m->slug, ['class' => 'badge bg-success text-decoration-none', 'target' => '_blank', 'rel' => 'noopener'])
                        : '<span class="badge bg-secondary">Draft</span>',
                ],
                GridActionHelper::crudColumn('{update} {delete}', 'Delete this product and its images for good?'),
            ],
        ]) ?>
    </div>
</div>
