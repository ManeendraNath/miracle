<?php
declare(strict_types=1);

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Account Security';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-4">

    <div class="
         d-flex
         justify-content-between
         align-items-center
         mb-4
         ">

        <div>

            <h2 class="mb-1">
                Account Security
            </h2>

            <div class="text-muted">
                Review successful sign-ins
                recorded for your account.
            </div>

        </div>

<?=
Html::a(
        'Change Password',
        [
            '/account/change-password',
        ],
        [
            'class' =>
            'btn btn-primary',
        ]
)
?>

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
                    'logged_in_at',
                    'label' =>
                    'Signed In',
                    'format' => [
                        'datetime',
                        'php:d M Y, h:i A',
                    ],
                ],
                [
                    'attribute' =>
                    'ip_address',
                    'label' =>
                    'IP Address',
                    'value' =>
                    static fn($model) =>
                    $model->ip_address ?: '-',
                ],
                [
                    'attribute' =>
                    'user_agent',
                    'label' =>
                    'Browser / Device',
                    'format' =>
                    'text',
                    'contentOptions' => [
                        'style' =>
                        'max-width:450px;'
                        . 'white-space:normal;',
                    ],
                ],
            ],
        ])
        ?>

    </div>

</div>
