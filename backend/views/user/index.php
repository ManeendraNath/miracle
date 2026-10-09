<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Manage System Accounts';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index card card-outline card-primary p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-users text-primary mr-2"></i> Client Directory</h3>
            <small class="text-muted">Review, register, or revoke access authorization privileges for accounts</small>
        </div>
        <?= Html::a('<i class="fas fa-user-plus mr-1"></i> Add New Client', ['create'], ['class' => 'btn btn-success px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'username',
                'format' => 'raw',
                'value' => function($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->username) . '</span>';
                }
            ],
            'email:email',
            [
                'attribute' => 'role',
                'format' => 'raw',
                'value' => function($model) {
                    $badgeClass = strtolower($model->role) === 'superadmin' ? 'badge-danger' : 'badge-info';
                    return '<span class="badge ' . $badgeClass . ' px-3 py-2 text-uppercase font-weight-bold" style="font-size: 0.75rem;">' . Html::encode($model->role ?? 'Client') . '</span>';
                }
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function($model) {
                    $isActive = (int)$model->status === 10; // Standard Yii2 User active identifier status
                    $badge = $isActive ? 'badge-success' : 'badge-warning';
                    $text = $isActive ? 'Active' : 'Inactive';
                    return '<span class="badge ' . $badge . ' px-2 py-1">' . $text . '</span>';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model) {
                        return Html::a('<i class="fas fa-trash-alt mr-1"></i> Delete', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-danger px-3 rounded-pill',
                            'data' => [
                                'confirm' => 'Are you completely sure you want to permanently revoke this client account?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
