<?php

use backend\models\searches\CustomerPreferenceSearch;
use common\models\CustomerPreference;
use yii\data\ActiveDataProvider;
use common\helpers\GridFilterHelper;
use yii\grid\GridView;
use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var CustomerPreferenceSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */
$this->title = 'Notification Preferences';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);

$booleanFilter = [
    '1' => 'Enabled',
    '0' => 'Disabled',
];

$badge = static function ($value): string {
    $enabled = (bool) $value;

    return Html::tag(
        'span',
        $enabled ? 'Enabled' : 'Disabled',
        [
                'class' =>
                'badge '
                . (
                $enabled ? 'bg-success' : 'bg-secondary'
                ),
            ]
    );
};
?>

<div class="customer-preference-index">

    <div class="card card-outline card-primary shadow-sm">

        <?= AdminIndexHelper::pageHeader('Review customer communication and reminder preferences.') ?>

        <div class="card-body p-0">

<?=
GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'layout' => GridPaginationHelper::gridLayout(),
    'filterRowOptions' => [
        'class' => 'bg-light',
    ],
    'tableOptions' => [
        'class' =>
        'table table-hover '
        . 'table-striped '
        . 'align-middle mb-0',
    ],
    'summaryOptions' => [
        'class' =>
        'text-muted small mb-0',
    ],
    'columns' => [
        [
            'class' =>
            'yii\grid\SerialColumn',
            'header' => '#',
            'headerOptions' => [
                'style' => 'width: 60px;',
            ],
        ],
        [
            'attribute' => 'customer',
            'label' => 'Customer',
            'format' => 'raw',
            'value' =>
            static function (
                CustomerPreference $model
            ): string {
                if ($model->user === null) {
                    return Html::tag(
                        'span',
                        'Unknown customer',
                        [
                                        'class' =>
                                        'text-muted',
                                    ]
                    );
                }

                $username = (string)
                        $model->user->username;

                $email = (string)
                        $model->user->email;

                return Html::tag(
                    'span',
                    Html::encode($username),
                    [
                            'class' =>
                            'fw-semibold',
                            'title' =>
                            $email,
                            'data-bs-toggle' =>
                            'tooltip',
                        ]
                );
            },
            'headerOptions' => [
                'style' =>
                'min-width: 180px;',
            ],
        ],
        [
            'attribute' =>
            'renewal_reminders',
            'label' =>
            'Renewal Reminders',
            'format' => 'raw',
            'value' =>
            static fn(
            CustomerPreference $model
            ): string =>
            $badge(
                $model
                    ->renewal_reminders
            ),
            'filter' => GridFilterHelper::select2($searchModel, 'renewal_reminders', $booleanFilter, 'All'),
            'contentOptions' => [
                'class' =>
                'text-center',
            ],
        ],
        [
            'attribute' =>
            'payment_reminders',
            'label' =>
            'Payment Reminders',
            'format' => 'raw',
            'value' =>
            static fn(
            CustomerPreference $model
            ): string =>
            $badge(
                $model
                    ->payment_reminders
            ),
            'filter' => GridFilterHelper::select2($searchModel, 'payment_reminders', $booleanFilter, 'All'),
            'contentOptions' => [
                'class' =>
                'text-center',
            ],
        ],
        [
            'attribute' =>
            'service_expiry_notifications',
            'label' =>
            'Service Expiry',
            'format' => 'raw',
            'value' =>
            static fn(
            CustomerPreference $model
            ): string =>
            $badge(
                $model
                    ->service_expiry_notifications
            ),
            'filter' => GridFilterHelper::select2($searchModel, 'service_expiry_notifications', $booleanFilter, 'All'),
            'contentOptions' => [
                'class' =>
                'text-center',
            ],
        ],
        [
            'attribute' =>
            'support_notifications',
            'label' =>
            'Support Updates',
            'format' => 'raw',
            'value' =>
            static fn(
            CustomerPreference $model
            ): string =>
            $badge(
                $model
                    ->support_notifications
            ),
            'filter' => GridFilterHelper::select2($searchModel, 'support_notifications', $booleanFilter, 'All'),
            'contentOptions' => [
                'class' =>
                'text-center',
            ],
        ],
        [
            'attribute' => 'updated_at',
            'label' => 'Last Updated',
            'format' => [
                'datetime',
                'php:d M Y, h:i A',
            ],
            'filter' => false,
            'contentOptions' => [
                'class' =>
                'text-nowrap',
            ],
        ],
        GridActionHelper::crudColumn(template: '{update}', urlCreator: static fn (string $action, CustomerPreference $model): array => ['update', 'userId' => $model->user_id]),
    ],
])
?>

        </div>

    </div>

</div>

