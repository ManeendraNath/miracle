<?php

use common\models\Admin;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */

$this->title = $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Admins', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']),
    Html::a('<i class="fas fa-trash me-1"></i> Delete', ['delete', 'id' => $model->id], ['class' => 'btn btn-danger', 'data' => ['confirm' => 'Are you sure you want to delete this item?', 'method' => 'post']]),
];
?>
<div class="admin-view">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body p-0">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered align-middle mb-0'],
                'attributes' => [
                    'id',
                    'username',
                    'email:email',
                    //'auth_key',
                    //'password_hash',
                    //'password_reset_token',
                    'role',
                    [
                        'attribute' => 'status',
                        'value' => Admin::getStatuses()[(int) $model->status] ?? 'Unknown',
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
