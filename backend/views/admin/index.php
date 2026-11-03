<?php

use common\helpers\GridActionHelper;
use common\helpers\AdminIndexHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Admin;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var backend\models\AdminSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Admins';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>
<div class="admin-index card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('Review and manage administrator accounts.', 'create', 'Create Admin') ?>
    <div class="card-body p-0">

    <!-- Polished GridView containing Column-Level Input Filters -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel, // 🚀 THE CURE: This enables the empty filtering row input boxes shown in your image!
        'layout' => GridPaginationHelper::gridLayout(),
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle mb-0'],
        'summaryOptions' => ['class' => 'text-muted small mb-0'],
        'filterRowOptions' => ['class' => 'bg-light'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary font-weight-bold'],
            ],
            [
                'attribute' => 'id',
                'label' => 'System ID Reference',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="text-secondary font-weight-bold">' . $model->id . '</span>';
                }
            ],
            [
                'attribute' => 'username',
                'label' => 'Unique Profile Handle',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->username) . '</span>';
                }
            ],
            [
                'attribute' => 'email',
                'label' => 'Email',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'email',
            ],
            [
                'attribute' => 'role',
                'filter' => GridFilterHelper::select2($searchModel, 'role', Admin::getRoles(), 'All Roles'),
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
            ],
            [
                'attribute' => 'status',
                'label' => 'Status',
                'filter' => GridFilterHelper::select2($searchModel, 'status', Admin::getStatuses(), 'All Statuses'),
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => static function (Admin $model): string {
                    $class = match ((int) $model->status) {
                        Admin::getStatusActive() => 'bg-success',
                        Admin::getStatusSuspended() => 'bg-warning text-dark',
                        Admin::getStatusDeleted() => 'bg-danger',
                        default => 'bg-secondary',
                    };

                    return Html::tag('span', Html::encode(Admin::getStatuses()[(int) $model->status] ?? 'Unknown'), ['class' => 'badge ' . $class]);
                },
            ],

            GridActionHelper::crudColumn(deleteMessage: 'Delete this administrator account?'),
        ],
    ]); ?>
</div>
</div>
