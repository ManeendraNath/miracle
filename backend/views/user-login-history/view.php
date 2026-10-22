<?php
declare(strict_types=1);

use common\models\UserLoginHistory;
use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var UserLoginHistory $model
 */
$this->title = 'Login Record #' . $model->id;

$this->params['breadcrumbs'][] = [
    'label' => 'Login & Security History',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-login-history-view">

    <div class="row">

        <div class="col-lg-9">

            <div class="card card-outline card-primary shadow-sm">

                <div class="card-header">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h3 class="card-title">
                                Login Details
                            </h3>

                            <div class="text-muted small">
                                Recorded customer authentication activity.
                            </div>

                        </div>

                        <?=
                        Html::a(
                                'Back to History',
                                ['index'],
                                [
                                    'class' =>
                                    'btn btn-light border',
                                ]
                        )
                        ?>

                    </div>

                </div>

                <div class="card-body p-0">

                    <?=
                    DetailView::widget([
                        'model' => $model,
                        'options' => [
                            'class' =>
                            'table table-bordered '
                            . 'align-middle mb-0',
                        ],
                        'attributes' => [
                            [
                                'label' =>
                                'Customer',
                                'value' =>
                                $model->user ? $model->user->username : 'Unknown',
                            ],
                            [
                                'label' =>
                                'Email Address',
                                'format' => 'email',
                                'value' =>
                                $model->user ? $model->user->email : null,
                            ],
                            [
                                'attribute' =>
                                'ip_address',
                                'label' =>
                                'IP Address',
                            ],
                            [
                                'attribute' =>
                                'user_agent',
                                'label' =>
                                'Browser / Device',
                            ],
                            [
                                'attribute' =>
                                'logged_in_at',
                                'label' =>
                                'Logged In At',
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

        </div>

    </div>

</div>