<?php

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Lead;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\LeadSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Leads';
$this->params['breadcrumbs'][] = $this->title;

/*
 * Registers shared GridView functionality:
 *
 * - Bootstrap 5 tooltips
 * - Dynamic records-per-page dropdown
 */
GridViewHelper::register($this);
?>

<div class="lead-index">

    <div class="card card-outline card-primary shadow-sm">

        <!-- Header -->
        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <div class="text-muted small">
                        Manage incoming client enquiries and lead sources.
                    </div>
                </div>

            </div>

        </div>


        <!-- Grid -->
        <div class="card-body p-0">

            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                /*
                 * Shared:
                 *
                 * - summary
                 * - records-per-page dropdown
                 * - responsive table
                 * - pager
                 */
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => [
                    'class' => 'text-muted small mb-0',
                ],
                'tableOptions' => [
                    'class' =>
                    'table table-hover table-striped align-middle mb-0',
                ],
                'filterRowOptions' => [
                    'class' => 'bg-light',
                ],
                'columns' => [
                    /*
                     * Serial Number
                     */
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => [
                            'style' => 'width: 60px;',
                            'class' => 'text-center',
                        ],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
                    /*
                     * Lead Name
                     */
                    [
                        'attribute' => 'lead_name',
                        'label' => 'Lead',
                        'format' => 'raw',
                        'value' => static function (Lead $model): string {
                            return Html::tag(
                                    'span',
                                    Html::encode($model->lead_name),
                                    ['class' => 'fw-semibold text-dark']
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 170px;'],
                    ],
                    /*
                     * Phone Number
                     */
                    [
                        'attribute' => 'phone_number',
                        'label' => 'Phone',
                        'value' => static function (Lead $model): string {
                            return $model->phone_number ?: '-';
                        },
                        'headerOptions' => ['style' => 'min-width: 140px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Lead Source
                     */
                    [
                        'attribute' => 'source',
                        'label' => 'Source',
                        'filter' => GridFilterHelper::select2($searchModel, 'source', Lead::getSources(), 'All Sources'),
                        'value' => static function (Lead $model): string {
                            return $model->source ?: '-';
                        },
                        'headerOptions' => ['style' => 'min-width: 180px;'],
                    ],
                    /*
                     * Created Date
                     *
                     * created_at is a UNIX timestamp and the
                     * SearchModel uses created_at_range.
                     */
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created',
                        'filter' => GridFilterHelper::dateRange($searchModel, 'created_at_range', 'Created date'),
                        'value' => static function (Lead $model): string {
                            return Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A');
                        },
                        'headerOptions' => ['style' => 'min-width: 190px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Shared CRUD actions.
                     */
                    GridActionHelper::crudColumn(deleteMessage: 'Are you sure you want to delete this lead?'),
                ],
            ])
            ?>
        </div>
    </div>
</div>