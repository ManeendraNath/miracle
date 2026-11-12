<?php

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Add New Client' => 'Create a customer account yourself. The customer is emailed that their account exists, and you are taken to their billing details next.',
    'Eye icon' => 'Open the customer, with buttons to email them and (Admins) see their notification preferences and login history.',
    'Pencil icon' => 'Change the customer\'s account details or status.',
    'Ban icon (Disable)' => 'Shown on Active customers. Suspends the account so they can no longer sign in. Their renewals and reminders carry on: disable their services or cancel invoices if those should stop too.',
    'Tick icon (Enable)' => 'Shown on any customer who is not Active. Makes the account Active so they can sign in again.',
    'Account Status' => 'Active can sign in. Inactive has signed up but not confirmed their email yet. Suspended was stopped by staff, or automatically after a month with incomplete billing details and nothing in use. Deleted: renewals and reminders stop.',
];

GridViewHelper::register($this);
?>
<div class="user-index card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('Review, filter and manage customer accounts.', 'create', 'Add New Client') ?>
    <div class="card-body p-0">

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel, // Enables column data filters row
        'layout' => GridPaginationHelper::gridLayout(),
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle mb-0'],
        'summaryOptions' => ['class' => 'text-muted small mb-0'],
        'filterRowOptions' => ['class' => 'bg-light'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-primary font-weight-bold'],
            ],
            [
                'attribute' => 'username',
                'label' => 'Client Username',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->username) . '</span>';
                }
            ],
            [
                'attribute' => 'email',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'email',
            ],
            [
                'attribute' => 'status',
                'label' => 'Account Status',
                'filter' => GridFilterHelper::select2(
                    $searchModel,
                    'status',
                    User::getStatuses(),
                    'All Statuses'
                ),
                'format' => 'raw',
                'value' => static function (User $model): string {
                    $status = (int) $model->status;
                    $statuses = User::getStatuses();

                    switch ($status) {
                        case User::getStatusActive():
                            $class = 'bg-success';
                            break;

                        case User::getStatusSuspended():
                            $class = 'bg-warning text-dark';
                            break;

                        case User::getStatusDeleted():
                            $class = 'bg-danger';
                            break;

                        default:
                            $class = 'bg-secondary';
                            break;
                    }

                    return Html::tag(
                        'span',
                        Html::encode($statuses[$status] ?? 'Unknown'),
                        [
                                'class' => 'badge ' . $class,
                            ]
                    );
                },
            ],
            [
                'attribute' => 'created_at',
                'label' => 'Created At',
                'filter' => GridFilterHelper::dateRange(
                    $searchModel,
                    'created_at_range',
                    'Created date'
                ),
                'value' => static function (User $model): string {
                    return Yii::$app->formatter->asDatetime(
                        $model->created_at,
                        'php:d M Y, h:i A'
                    );
                },
                'headerOptions' => [
                    'style' => 'width: 220px;',
                ],
            ],
            [
                'attribute' => 'updated_at',
                'label' => 'Updated At',
                'filter' => GridFilterHelper::dateRange(
                    $searchModel,
                    'updated_at_range',
                    'Updated date'
                ),
                'value' => static function (User $model): string {
                    return Yii::$app->formatter->asDatetime(
                        $model->updated_at,
                        'php:d M Y, h:i A'
                    );
                },
                'headerOptions' => [
                    'style' => 'width: 220px;',
                ],
            ],
            GridActionHelper::crudColumn(
                template: '{view} {update} {toggle-status}',
                isEnabled: static fn(User $model): bool => (int) $model->status === User::getStatusActive()
            ),
        ],
    ]);
    ?>
</div>
</div>
