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
$this->params['subtitle'] = 'Recorded customer authentication activity.';
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back to History', ['index'], ['class' => 'btn btn-light border'])];
?>

<div class="user-login-history-view">

    <div class="row">

        <div class="col-lg-9">

            <div class="card card-outline card-primary shadow-sm">

                <div class="card-body p-0">

                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => [
                            'class' => 'table table-bordered ' . 'align-middle mb-0',
                        ],
                        'attributes' => [
                            [
                                'label' => 'Customer',
                                'value' => $model->user->username,
                            ],
                            [
                                'label' => 'Email Address',
                                'format' => 'email',
                                'value' => $model->user->email,
                            ],
                            [
                                'attribute' => 'ip_address',
                                'label' => 'IP Address',
                            ],
                            [
                                'attribute' => 'user_agent',
                                'label' => 'Browser / Device',
                            ],
                            [
                                'attribute' => 'logged_in_at',
                                'label' => 'Logged In At',
                                'format' => ['datetime', 'php:d M Y, h:i A'],
                            ],
                        ],
                    ]) ?>

                </div>

            </div>

        </div>

    </div>

</div>