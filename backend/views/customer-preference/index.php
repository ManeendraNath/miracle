<?php

use backend\models\searches\CustomerPreferenceSearch;
use common\models\CustomerPreference;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var CustomerPreferenceSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */
$this->title = 'Notification Preferences';
$this->params['breadcrumbs'][] = $this->title;

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

        <div class="card-header">

            <div
                class="d-flex
                justify-content-between
                align-items-center"
                >

                <div>

                    <h3 class="card-title mb-1">
                        Customer Notification Preferences
                    </h3>

                    <div class="text-muted small">
                        Review customer communication
                        and reminder preferences.
                    </div>

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

<?=
GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'tableOptions' => [
        'class' =>
        'table table-hover '
        . 'table-striped '
        . 'align-middle mb-0',
    ],
    'summaryOptions' => [
        'class' =>
        'text-muted small px-3 pt-3',
    ],
    'columns' => [
        [
            'class' =>
            'yii\grid\SerialColumn',
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
            'filter' => $booleanFilter,
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
            'filter' => $booleanFilter,
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
            'filter' => $booleanFilter,
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
            'filter' => $booleanFilter,
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
        [
            'label' => 'Actions',
            'format' => 'raw',
            'value' =>
            static function (
                    CustomerPreference $model
            ): string {
                return Html::a(
                        '<i class="fas fa-edit"></i>',
                        [
                            'update',
                            'userId' =>
                            $model->user_id,
                        ],
                        [
                            'class' =>
                            'btn btn-sm '
                            . 'btn-outline-primary',
                            'title' =>
                            'Manage Preferences',
                            'data-bs-toggle' =>
                            'tooltip',
                        ]
                );
            },
            'headerOptions' => [
                'style' =>
                'width: 90px;',
                'class' =>
                'text-center',
            ],
            'contentOptions' => [
                'class' =>
                'text-center',
            ],
        ],
    ],
])
?>

            </div>

        </div>

    </div>

</div>

                <?php
                $this->registerJs(<<<'JS'
document
    .querySelectorAll('[data-bs-toggle="tooltip"]')
    .forEach(function (element) {
        new bootstrap.Tooltip(element);
    });
JS);
                ?>