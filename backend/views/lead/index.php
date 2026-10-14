<?php

use common\helpers\GridFilterHelper;
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
                'label' => 'Lead Source',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 180px;'],
                'filter' => GridFilterHelper::select2(
                    $searchModel,
                    'source',
                    [
                        'Google Search' => 'Google Search',
                        'Social Media' => 'Social Media Referral',
                        'Direct Client' => 'Direct/Inbound Call',
                        'Frontend Request' => 'Website Audit Form'
                    ],
                    'All Sources'
                ),
                'format' => 'raw',
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
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Control Box',
                'headerOptions' => ['class' => 'text-primary text-center', 'style' => 'width: 100px;'],
                'contentOptions' => ['class' => 'text-center'],
            ],
        ],
    ]);
    ?>
</div>
