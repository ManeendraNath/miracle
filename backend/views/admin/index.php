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
/** @var backend\models\searches\AdminSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Admins';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'What this is' => 'The staff accounts that can sign in to this admin panel. Only Superadmins can open this page.',
    'Role' => 'Manager: customers, services, support and leads. Admin: also billing, payments, coupons, login history and cron jobs. Superadmin: also staff accounts and site settings.',
    'Status' => 'Only Active staff can sign in. Set someone Suspended to block them but keep the account.',
    'Create Admin' => 'Add a staff member. Tell them their username and password yourself: no email is sent. They must set up two-factor sign-in before they can use the panel.',
    'Eye icon' => 'Open the account.',
    'Pencil icon' => 'Change the username, email, role, status or password, or turn off two-factor sign-in for someone who lost their phone.',
    'Trash icon' => 'Deletes the account for good. You cannot delete yourself or the last active Superadmin.',
];

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
