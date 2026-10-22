<?php
declare(strict_types=1);

use backend\models\searches\UserLoginHistorySearch;
use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\models\UserLoginHistory;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var UserLoginHistorySearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */
$this->title = 'Login & Security History';
$this->params['breadcrumbs'][] = $this->title;

GridActionHelper::registerTooltips($this);
?>

<div class="user-login-history-index">

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="card-title mb-1">
                        Login & Security History
                    </h3>

                    <div class="text-muted small">
                        Review customer account login activity,
                        IP addresses and browser/device information.
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
    'layout' =>
    GridPaginationHelper::gridLayout(),
    'summaryOptions' => [
        'class' =>
        'text-muted small mb-0',
    ],
    'tableOptions' => [
        'class' =>
        'table table-hover '
        . 'table-striped '
        . 'align-middle mb-0',
    ],
    'filterRowOptions' => [
        'class' => 'bg-light',
    ],
    'columns' => [
        [
            'class' =>
            'yii\grid\SerialColumn',
            'header' => '#',
            'headerOptions' => [
                'style' =>
                'width: 60px;',
                'class' =>
                'text-center',
            ],
            'contentOptions' => [
                'class' =>
                'text-center text-muted',
            ],
        ],
        /*
         * Customer
         */
        [
            'attribute' => 'customer',
            'label' => 'Customer',
            'format' => 'raw',
            'value' =>
            static function (
                    UserLoginHistory $model
            ): string {
                if ($model->user === null) {
                    return Html::tag(
                                    'span',
                                    'Unknown',
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
                            'data-bs-placement' =>
                            'top',
                        ]
                );
            },
            'headerOptions' => [
                'style' =>
                'min-width: 160px;',
            ],
        ],
        /*
         * IP address
         */
        [
            'attribute' =>
            'ip_address',
            'label' =>
            'IP Address',
            'format' => 'raw',
            'value' =>
            static function (
                    UserLoginHistory $model
            ): string {
                return Html::tag(
                        'code',
                        Html::encode(
                                $model->ip_address ?: '-'
                        ),
                        [
                            'class' =>
                            'text-dark',
                        ]
                );
            },
            'headerOptions' => [
                'style' =>
                'width: 160px;',
            ],
            'contentOptions' => [
                'class' =>
                'text-nowrap',
            ],
        ],
        /*
         * Browser / User Agent
         */
        [
            'attribute' =>
            'user_agent',
            'label' =>
            'Browser / Device',
            'format' => 'raw',
            'value' =>
            static function (
                    UserLoginHistory $model
            ): string {
                $userAgent = trim(
                        (string)
                        $model->user_agent
                );

                if ($userAgent === '') {
                    return '-';
                }

                $short = mb_strlen($userAgent) > 65 ? mb_substr(
                                $userAgent,
                                0,
                                62
                        ) . '...' : $userAgent;

                return Html::tag(
                        'span',
                        Html::encode($short),
                        [
                            'title' =>
                            $userAgent,
                            'data-bs-toggle' =>
                            'tooltip',
                            'data-bs-placement' =>
                            'top',
                        ]
                );
            },
            'headerOptions' => [
                'style' =>
                'min-width: 250px;',
            ],
        ],
        /*
         * Login timestamp
         */
        [
            'attribute' =>
            'logged_in_at',
            'label' =>
            'Login Time',
            'filter' =>
            GridFilterHelper::dateRange(
                    $searchModel,
                    'logged_in_at_range',
                    'Login date'
            ),
            'value' =>
            static function (
                    UserLoginHistory $model
            ): string {
                return Yii::$app
                        ->formatter
                        ->asDatetime(
                                $model->logged_in_at,
                                'php:d M Y, h:i A'
                        );
            },
            'headerOptions' => [
                'style' =>
                'width: 220px;',
            ],
            'contentOptions' => [
                'class' =>
                'text-nowrap',
            ],
        ],
        /*
         * Read-only action.
         */
        GridActionHelper::crudColumn(
                template: '{view}',
        ),
    ],
])
?>

            </div>

        </div>

    </div>

</div>
``