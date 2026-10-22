<?php
declare(strict_types=1);

use common\models\ServiceRenewalHistory;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Renewal History';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-4">

    <div class="mb-4">

        <h2 class="mb-1">
            Renewal History
        </h2>

        <div class="text-muted">
            Review successfully completed service renewals.
        </div>

    </div>

    <div class="card shadow-sm">

<?=
GridView::widget([
    'dataProvider' =>
    $dataProvider,
    'layout' =>
    "{items}\n{pager}",
    'tableOptions' => [
        'class' =>
        'table table-hover align-middle mb-0',
    ],
    'columns' => [
        [
            'attribute' =>
            'item_type',
            'label' =>
            'Service',
        ],
        [
            'attribute' =>
            'billed_amount',
            'label' =>
            'Amount',
            'value' =>
            static fn(
            ServiceRenewalHistory $model
            ): string =>
                    Yii::$app
                    ->formatter
                    ->asCurrency(
                            $model->billed_amount,
                            'INR'
                    ),
        ],
        [
            'attribute' =>
            'start_date',
            'label' =>
            'Period Start',
            'format' => [
                'date',
                'php:d M Y',
            ],
        ],
        [
            'attribute' =>
            'end_date',
            'label' =>
            'Period End',
            'format' => [
                'date',
                'php:d M Y',
            ],
        ],
        [
            'attribute' =>
            'renewal_period_months',
            'label' =>
            'Period',
            'value' =>
            static fn(
            ServiceRenewalHistory $model
            ): string =>
            $model
            ->renewal_period_months
            . ' month(s)',
        ],
        [
            'attribute' =>
            'logged_at',
            'label' =>
            'Completed',
            'format' => [
                'datetime',
                'php:d M Y, h:i A',
            ],
        ],
    ],
])
?>

    </div>

</div>