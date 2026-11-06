<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Testimonial;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Testimonials';
$this->params['breadcrumbs'][] = $this->title;
GridViewHelper::register($this);
?>
<div class="testimonial-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Client quotes for the home page. The section stays hidden until one is published.', 'create', 'Add Testimonial') ?>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => 'No testimonials yet.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    'sort_order',
                    'client_name',
                    [
                        'label' => 'From',
                        'value' => static fn(Testimonial $model): string => $model->user_id ? 'Customer' : 'Staff',
                    ],
                    [
                        'label' => 'Role, company and country',
                        'value' => static fn(Testimonial $model): string => $model->byline() ?: '-',
                    ],
                    [
                        'attribute' => 'quote',
                        'value' => static fn(Testimonial $model): string => StringHelper::truncate($model->quote, 80),
                    ],
                    [
                        'attribute' => 'is_published',
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => static fn(Testimonial $model): string => Html::tag(
                            'span',
                            $model->is_published ? 'Published' : 'Hidden',
                            ['class' => 'badge ' . ($model->is_published ? 'bg-success' : 'bg-secondary')],
                        ),
                    ],
                    GridActionHelper::crudColumn('{update} {delete}'),
                ],
            ]) ?>
        </div>
    </div>
</div>
