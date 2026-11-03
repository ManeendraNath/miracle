<?php

declare(strict_types=1);

use backend\models\searches\CustomerBillingProfileSearch;
use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\CustomerBillingProfile;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var CustomerBillingProfileSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Customer Billing Profiles';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);

$users = ArrayHelper::map(User::find()->orderBy(['username' => SORT_ASC])->all(), 'id', 'username');
?>

<div class="customer-billing-profile-index">

    <div class="card card-outline card-primary shadow-sm">

        <?= AdminIndexHelper::pageHeader(
            'Legal billing information used when invoices are generated.',
            'create',
            'Add Billing Profile',
        ) ?>

        <div class="card-body p-0">

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => ['class' => 'text-muted small mb-3'],
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'filterRowOptions' => ['class' => 'bg-light'],
                'emptyText' => 'No billing profiles found.',
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
                    [
                        'attribute' => 'user_id',
                        'label' => 'Client',
                        'value' => static fn (CustomerBillingProfile $model): string => $model->user?->username ?? '-',
                        'filter' => GridFilterHelper::select2($searchModel, 'user_id', $users, 'All Clients'),
                    ],
                    'legal_name',
                    [
                        'attribute' => 'state',
                        'label' => 'State / Country',
                        'value' => static fn (CustomerBillingProfile $model): string => (string) ($model->isForeign() ? $model->country : $model->state),
                    ],
                    [
                        'attribute' => 'gstin',
                        'label' => 'GSTIN',
                        'value' => static fn (CustomerBillingProfile $model): string => $model->gstin ?: '-',
                    ],
                    [
                        'attribute' => 'pan',
                        'label' => 'PAN',
                        'value' => static fn (CustomerBillingProfile $model): string => $model->pan ?: '-',
                    ],
                    GridActionHelper::crudColumn(template: '{update}'),
                ],
            ]) ?>

        </div>

    </div>

</div>
