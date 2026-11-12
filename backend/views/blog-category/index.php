<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\BlogCategory;
use common\models\BlogPost;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Blog Categories';
$this->params['breadcrumbs'][] = ['label' => 'Blog Posts', 'url' => ['/blog-post/index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Categories' => 'Group posts by topic, e.g. Web Development, E-commerce, Case Studies. Visitors can filter the blog by them, at /blog/category/web-address.',
    'Trash icon' => 'Deletes the category; its posts stay, with no category.',
    'Add Category' => 'A short name; its web address is made from it.',
];
GridViewHelper::register($this);
?>
<div class="blog-category-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Topics visitors can filter the blog by.', 'create', 'Add Category') ?>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => 'No categories yet.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    'name',
                    ['attribute' => 'slug', 'value' => static fn(BlogCategory $model): string => '/blog/category/' . $model->slug],
                    ['label' => 'Posts', 'value' => static fn(BlogCategory $model): int => (int) BlogPost::find()->where(['category_id' => $model->id])->count()],
                    GridActionHelper::crudColumn('{update} {delete}', 'Delete this category? Its posts stay, with no category.'),
                ],
            ]) ?>
        </div>
    </div>
</div>
