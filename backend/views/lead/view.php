<?php

use yii\bootstrap5\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */

$this->title = 'Lead Assessment Profile #' . $model->id;
?>
<div class="lead-view container py-4" style="font-family: 'Inter', system-ui, sans-serif;">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <?= Html::a('← Back to CRM Pipeline Grid', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <div>
            <?= Html::a('Modify Pipeline Status 📝', ['update', 'id' => $model->id], [
                'class' => 'btn text-white fw-bold px-4',
                'style' => 'background-color: #00a3e0; border: none;'
            ]) ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem;">
        <h3 class="fw-bold text-dark border-bottom pb-3 mb-4">📋 Comprehensive Specification Matrix</h3>

        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                'name',
                'email:email',
                'company_url:url',
                'current_framework',
                'hosting_environment',
                [
                    'attribute' => 'message',
                    'format' => 'ntext',
                    'contentOptions' => ['style' => 'background-color: #f8fafc; font-size: 0.95rem; border-left: 4px solid #00a3e0; padding: 15px;']
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => Html::tag('span', Html::encode($model->status), ['class' => 'badge bg-secondary px-3 py-2 text-uppercase fs-6'])
                ],
                [
                    'attribute' => 'created_at',
                    'value' => date('d-M-Y H:i:s', (int) $model->created_at),
                ],
            ],
        ]) ?>
    </div>
</div>
