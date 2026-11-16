<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\BlogPost;
use common\models\BlogPostView;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Blog Posts';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Status' => 'Draft: only staff can see it. Scheduled: Published, but its publish time is still ahead; it goes live by itself then. Published: on the website at /blog.',
    'Views' => 'Readers of the post on the website: each visitor counts once a day. Search engines, link previews and staff previews are not counted. Below it, the last 30 days.',
    'Eye icon' => 'Preview the post as visitors will see it, drafts too.',
    'Pencil icon' => 'Edit the post, change its status or publish time.',
    'Trash icon' => 'Delete the post for good. Its web address stops working, so links to it from elsewhere will break; set it back to Draft instead to hide it.',
    'Write a post' => 'Starts a new post as a Draft.',
    'Getting readers' => 'Write what clients abroad search for, post regularly, share each post on LinkedIn, and check Google Search Console to see which searches bring visitors.',
];
GridViewHelper::register($this);
$views = BlogPostView::counts(array_map('intval', $dataProvider->getKeys()));
?>
<div class="blog-post-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Posts for the public blog at /blog. Drafts are only visible to staff.', 'create', 'Write a post') ?>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => 'No posts yet.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    ['attribute' => 'title', 'format' => 'raw', 'value' => static fn(BlogPost $model): string => Html::a(Html::encode($model->title), ['update', 'id' => $model->id], ['class' => 'fw-semibold'])],
                    ['label' => 'Category', 'value' => static fn(BlogPost $model): string => $model->category->name ?? '-'],
                    [
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => static function (BlogPost $model): string {
                            $state = $model->stateLabel();

                            return Html::tag('span', $state, ['class' => 'badge ' . ['Draft' => 'bg-secondary', 'Scheduled' => 'bg-info text-dark', 'Published' => 'bg-success'][$state]]);
                        },
                    ],
                    ['attribute' => 'published_at', 'format' => ['datetime', 'php:d M Y, H:i'], 'label' => 'Publish time'],
                    [
                        'label' => 'Views',
                        'format' => 'raw',
                        'contentOptions' => ['class' => 'text-nowrap'],
                        'value' => static function (BlogPost $model) use ($views): string {
                            $count = $views[(int) $model->id] ?? ['total' => 0, 'recent' => 0];

                            return Html::tag('span', number_format($count['total']), ['class' => 'fw-semibold'])
                                . Html::tag('div', number_format($count['recent']) . ' in 30 days', ['class' => 'small text-body-secondary']);
                        },
                    ],
                    GridActionHelper::crudColumn(
                        template: '{view} {update} {delete}',
                        deleteMessage: 'Delete this post for good? Its web address stops working. To hide it instead, set it back to Draft.',
                        urlCreator: static fn(string $action, BlogPost $model): array => [$action === 'view' ? 'preview' : $action, 'id' => $model->id],
                    ),
                ],
            ]) ?>
        </div>
    </div>
</div>
