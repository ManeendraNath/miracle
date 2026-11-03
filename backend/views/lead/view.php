<?php

declare(strict_types=1);

use backend\controllers\BaseController;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Lead $model */

$this->title = $model->lead_name;
$this->params['breadcrumbs'][] = ['label' => 'Leads', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Lead #' . (int) $model->id;
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border'])];
if (BaseController::canAccess(Yii::$app->user->identity, 'lead', 'update')) {
    $this->params['actions'][] = Html::a('<i class="fas fa-edit me-1"></i> Update Status', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']);
}
?>
<div class="lead-view">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body p-0">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table table-bordered align-middle mb-0'],
                'attributes' => [
                    'lead_name',
                    'phone_number',
                    'source',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => Html::tag('span', Html::encode($model->status), ['class' => 'badge bg-secondary']),
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>
</div>
