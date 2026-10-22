<?php
declare(strict_types=1);

use common\services\UpcomingExpiryService;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Renewal Monitoring';
$this->params['breadcrumbs'][] = $this->title;

$typeLabels = UpcomingExpiryService::getTypes();
$windowLabels = UpcomingExpiryService::getWindows();

$routeMap = [
    UpcomingExpiryService::TYPE_DOMAIN => '/domains/view',
    UpcomingExpiryService::TYPE_HOSTING => '/hosting/view',
    UpcomingExpiryService::TYPE_MAINTENANCE => '/maintenance/view',
];

$typeClasses = [
    UpcomingExpiryService::TYPE_DOMAIN => 'bg-primary',
    UpcomingExpiryService::TYPE_HOSTING => 'bg-info',
    UpcomingExpiryService::TYPE_MAINTENANCE => 'bg-secondary',
];

$this->registerJs(<<<'JS'
if (
    typeof bootstrap !== 'undefined'
    && typeof bootstrap.Tooltip !== 'undefined'
) {
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
}
JS);
?>

<div class="renewal-monitoring-index">

    <div class="mb-3">
        <h4 class="mb-1">Renewal Monitoring</h4>

        <div class="text-muted small">
            Monitor expired and upcoming domain, hosting and maintenance service renewals.
        </div>
    </div>

    <div class="row">

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">

                <div class="inner">
                    <h3><?= (int) $summary['expired'] ?></h3>
                    <p>Expired Services</p>
                </div>

                <div class="icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>

            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">

                <div class="inner">
                    <h3><?= (int) $summary['next7'] ?></h3>
                    <p>Expiring Within 7 Days</p>
                </div>

                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>

            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">

                <div class="inner">
                    <h3><?= (int) $summary['next30'] ?></h3>
                    <p>Expiring Within 30 Days</p>
                </div>

                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>

            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">

                <div class="inner">
                    <h3><?= (int) $summary['next60'] ?></h3>
                    <p>Expiring Within 60 Days</p>
                </div>

                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>

            </div>
        </div>

    </div>

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-1"></i>
                Filters
            </h3>
        </div>

        <div class="card-body">

<?= Html::beginForm(['/renewal-monitoring/index'], 'get') ?>

            <div class="row">

                <div class="col-lg-3 col-md-6 mb-3">

<?=
Html::label(
        'Service Type',
        'type',
        [
            'class' => 'form-label fw-semibold',
        ]
)
?>

                    <?=
                    Html::dropDownList(
                            'type',
                            $filters['type'],
                            $typeLabels,
                            [
                                'id' => 'type',
                                'class' => 'form-control',
                                'prompt' => 'All Service Types',
                            ]
                    )
                    ?>

                </div>

                <div class="col-lg-4 col-md-6 mb-3">

                    <?=
                    Html::label(
                            'Customer',
                            'user_id',
                            [
                                'class' => 'form-label fw-semibold',
                            ]
                    )
                    ?>

                    <?=
                    Html::dropDownList(
                            'user_id',
                            $filters['user_id'],
                            $customers,
                            [
                                'id' => 'user_id',
                                'class' => 'form-control',
                                'prompt' => 'All Customers',
                            ]
                    )
                    ?>

                </div>

                <div class="col-lg-3 col-md-6 mb-3">

                    <?=
                    Html::label(
                            'Expiry Window',
                            'window',
                            [
                                'class' => 'form-label fw-semibold',
                            ]
                    )
                    ?>

                    <?=
                    Html::dropDownList(
                            'window',
                            $filters['window'],
                            $windowLabels,
                            [
                                'id' => 'window',
                                'class' => 'form-control',
                            ]
                    )
                    ?>

                </div>

                <div class="col-lg-2 col-md-6 mb-3 d-flex align-items-end">

            <?=
            Html::submitButton(
                    '<i class="fas fa-search mr-1"></i> Apply',
                    [
                        'class' => 'btn btn-primary w-100',
                    ]
            )
            ?>

                </div>

            </div>

<?=
Html::a(
        '<i class="fas fa-times mr-1"></i> Clear Filters',
        ['/renewal-monitoring/index'],
        [
            'class' => 'btn btn-sm btn-outline-secondary',
        ]
)
?>

<?= Html::endForm() ?>

        </div>

    </div>

    <div class="card card-outline card-info shadow-sm">

        <div class="card-header">
            <h3 class="card-title">Service Expiries</h3>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <?=
                GridView::widget([
                    'dataProvider' => $dataProvider,
                    'layout' => "{summary}\n{items}\n{pager}",
                    'summaryOptions' => [
                        'class' => 'text-muted small p-3 mb-0',
                    ],
                    'tableOptions' => [
                        'class' => 'table table-hover table-striped align-middle mb-0',
                    ],
                    'emptyText' => 'No service expiries match the selected filters.',
                    'emptyTextOptions' => [
                        'class' => 'text-center text-muted py-5',
                    ],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'header' => '#',
                            'headerOptions' => [
                                'style' => 'width: 60px;',
                            ],
                        ],
                        [
                            'attribute' => 'username',
                            'label' => 'Customer',
                            'format' => 'raw',
                            'value' => static function (array $item): string {
                                $username = $item['username'] ?: 'Unknown';
                                $email = $item['email'] ?: 'Email not available';

                                return Html::tag(
                                        'span',
                                        Html::encode($username),
                                        [
                                            'class' => 'fw-semibold text-primary',
                                            'title' => $email,
                                            'data-bs-toggle' => 'tooltip',
                                            'data-bs-placement' => 'top',
                                            'style' => 'cursor: help;',
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 160px;',
                            ],
                        ],
                        [
                            'attribute' => 'name',
                            'label' => 'Service',
                            'format' => 'raw',
                            'value' => static function (array $item): string {
                                return Html::tag(
                                        'span',
                                        Html::encode($item['name']),
                                        [
                                            'class' => 'fw-semibold text-dark',
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 220px;',
                            ],
                        ],
                        [
                            'attribute' => 'type',
                            'label' => 'Type',
                            'format' => 'raw',
                            'value' => static function (array $item) use (
                                    $typeLabels,
                                    $typeClasses
                            ): string {
                                $type = (string) $item['type'];

                                return Html::tag(
                                        'span',
                                        Html::encode(
                                                $typeLabels[$type] ?? ucfirst($type)
                                        ),
                                        [
                                            'class' => 'badge '
                                            . ($typeClasses[$type] ?? 'bg-dark'),
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'width: 140px;',
                            ],
                        ],
                        [
                            'attribute' => 'expiry_date',
                            'label' => 'Expiry',
                            'value' => static function (array $item): string {
                                return Yii::$app->formatter->asDate(
                                        $item['expiry_date'],
                                        'php:d M Y'
                                );
                            },
                            'headerOptions' => [
                                'style' => 'width: 170px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'days_remaining',
                            'label' => 'Remaining',
                            'format' => 'raw',
                            'value' => static function (array $item): string {
                                $remaining = (int) $item['days_remaining'];

                                if ($remaining < 0) {
                                    $days = abs($remaining);

                                    $label = 'Expired '
                                            . $days
                                            . ' day'
                                            . ($days === 1 ? '' : 's')
                                            . ' ago';

                                    $class = 'bg-danger';
                                } elseif ($remaining === 0) {
                                    $label = 'Expires today';
                                    $class = 'bg-danger';
                                } elseif ($remaining <= 7) {
                                    $label = $remaining
                                            . ' day'
                                            . ($remaining === 1 ? '' : 's');

                                    $class = 'bg-danger';
                                } elseif ($remaining <= 30) {
                                    $label = $remaining . ' days';
                                    $class = 'bg-warning text-dark';
                                } else {
                                    $label = $remaining . ' days';
                                    $class = 'bg-info';
                                }

                                return Html::tag(
                                        'span',
                                        Html::encode($label),
                                        [
                                            'class' => 'badge ' . $class,
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'width: 180px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'label' => 'Action',
                            'format' => 'raw',
                            'value' => static function (array $item) use (
                                    $routeMap
                            ): string {
                                $route = $routeMap[$item['type']] ?? null;

                                if ($route === null) {
                                    return '';
                                }

                                return Html::a(
                                        '<i class="fas fa-eye"></i>',
                                        [
                                            $route,
                                            'id' => $item['id'],
                                        ],
                                        [
                                            'class' => 'btn btn-sm btn-outline-info',
                                            'title' => 'View Service',
                                            'data-bs-toggle' => 'tooltip',
                                            'data-bs-placement' => 'top',
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'class' => 'text-center',
                                'style' => 'width: 90px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-center',
                            ],
                        ],
                    ],
                ])
                ?>

            </div>

        </div>

    </div>

</div>