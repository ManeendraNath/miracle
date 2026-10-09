<?php

use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\LeadSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Inbound Leads CRM';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lead-index card card-outline card-dark p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Process incoming agency customer deals, pipeline opportunities windows, and context logs</small>
        </div>
        <?= Html::a('<i class="fas fa-plus-square mr-1"></i> Add Manual Lead', ['create'], ['class' => 'btn btn-dark px-4 rounded-pill shadow-sm font-weight-bold']) ?>
    </div>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel, // Mounts the advanced filter engine rows layout
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary'],
            ],
            [
                'attribute' => 'lead_name',
                'headerOptions' => ['class' => 'text-primary'],
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->lead_name) . '</span>';
                }
            ],
            [
                'attribute' => 'phone_number',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 180px;']
            ],
            [
                'attribute' => 'source',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 180px;'],
                // ADVANCED DROPDOWN SEARCH FILTER SELECTOR
                'filter' => [
                    'Google Search' => 'Google Search',
                    'Social Media' => 'Social Media Referral',
                    'Direct Client' => 'Direct/Inbound Call',
                    'Frontend Request' => 'Website Audit Form'
                ]
            ],
            [
                'attribute' => 'date_range',
                'label' => 'Capture Timeline Date',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 250px;'],
                'value' => function ($model) {
                    return date('d M Y', strtotime($model->created_at));
                },
                // 📅 INTEGRATED ADAPTIVE GRAPHICAL DATE RANGE PICKER WIDGET
                'filter' => \kartik\daterange\DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'date_range',
                    'convertFormat' => true,
                    'pluginOptions' => [
                        'locale' => ['format' => 'Y-m-d', 'separator' => ' - '],
                        'opens' => 'left'
                    ],
                    'options' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Select range spread...']
                ]),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Control Box',
                'headerOptions' => ['class' => 'text-primary text-center', 'style' => 'width: 100px;'],
                'contentOptions' => ['class' => 'text-center'],
            ],
        ],
    ]);
    ?>
</div>
