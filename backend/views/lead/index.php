<?php

use yii\grid\GridView;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var backend\models\AuditRequestSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Infrastructure Audit Inbound Inquiries CRM';
?>
<div class="lead-index container-fluid py-4" style="font-family: 'Inter', system-ui, sans-serif;">

    <div class="p-5 text-white mb-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 0.75rem;">
        <h1 class="fw-bold m-0"><?= Html::encode($this->title) ?></h1>
        <p class="mt-2 mb-0 opacity-75" style="color: #cbd5e1;">Review potential client modernizations specifications, verify system frameworks targets, and track pipeline metrics.</p>
    </div>

    <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 0.75rem;">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'table table-hover table-striped align-middle border-0 m-0'],
            'pager' => [
                'class' => \yii\bootstrap5\LinkPager::class,
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [
                    'attribute' => 'name',
                    'format' => 'raw',
                    'value' => function($model) {
                        return Html::tag('span', Html::encode($model->name), ['class' => 'fw-bold text-dark']);
                    }
                ],
                'email:email',
                [
                    'attribute' => 'company_url',
                    'format' => 'raw',
                    'value' => function($model) {
                        return !empty($model->company_url) 
                            ? Html::a(Html::encode($model->company_url), $model->company_url, ['target' => '_blank', 'style' => 'color: #00a3e0;']) 
                            : Html::tag('span', 'Not Specified', ['class' => 'text-muted small']);
                    }
                ],
                [
                    'attribute' => 'current_framework',
                    'value' => 'current_framework',
                    'filter' => ['Yii1' => 'Yii1 Legacy', 'Yii2' => 'Yii2 Standard', 'Core PHP' => 'Core PHP Stack', 'Laravel' => 'Laravel Engine']
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'filter' => ['Pending' => 'Pending', 'In Progress' => 'In Progress', 'Completed' => 'Completed', 'Rejected' => 'Rejected'],
                    'value' => function($model) {
                        $badgeClass = match($model->status) {
                            'Completed' => 'bg-success',
                            'In Progress' => 'bg-info text-dark',
                            'Rejected' => 'bg-danger',
                            default => 'bg-warning text-dark',
                        };
                        return Html::tag('span', Html::encode($model->status), ['class' => "badge $badgeClass px-3 py-2 text-uppercase fw-semibold"]);
                    }
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Received Date',
                    'value' => function($model) {
                        return date('d-M-Y H:i', (int) $model->created_at);
                    }
                ],

                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('👁️ View', $url, ['class' => 'btn btn-sm btn-outline-dark me-1']);
                        },
                        'update' => function ($url, $model) {
                            return Html::a('📝 Status', $url, ['class' => 'btn btn-sm text-white', 'style' => 'background-color: #00a3e0; border: none;']);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
