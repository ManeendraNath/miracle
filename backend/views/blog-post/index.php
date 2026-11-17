<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\BlogPost;
use common\models\BlogPostView;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var bool $inTrash */
/** @var int $trashCount */

$this->title = 'Blog Posts';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Status' => 'Draft: only staff can see it. Scheduled: Published, but its publish time is still ahead; it goes live by itself then. Published: on the website at /blog.',
    'Views' => 'Readers of the post on the website: each visitor counts once a day. Search engines, link previews and staff previews are not counted. Below it, the last 30 days.',
    'Eye icon' => 'Preview the post as visitors will see it, drafts too.',
    'Chart icon' => 'Stats: views a day for the last 30 days, comments, newsletter emails and old web addresses that lead to it.',
    'Copy icon' => 'Duplicate: a Draft copy with the same text, category, cover and tags, to start a similar post.',
    'Pencil icon' => 'Edit the post, change its status or publish time.',
    'Trash icon' => 'Move to the trash: off the website and out of the sitemap at once. Restore it from the Trash tab within ' . BlogPost::TRASH_DAYS . ' days; after that it is deleted for good by itself. In the Trash tab, the trash icon deletes it for good straight away.',
    'Ticked posts' => 'Tick posts, choose an action under the list (Publish, Set to Draft, Move to trash; in the Trash tab Restore or Delete for good) and click Apply.',
    'Changing a web address' => 'The old address keeps working: it sends readers and search engines to the new one.',
    'Write a post' => 'Starts a new post as a Draft.',
    'Getting readers' => 'Write what clients abroad search for, post regularly, share each post on LinkedIn, and check Google Search Console to see which searches bring visitors.',
];
GridViewHelper::register($this);
$this->registerJs(<<<'JS'
document.getElementById('blog-bulk').addEventListener('submit', (event) => {
    const action = event.target.querySelector('#bulk-action');
    const ticked = document.querySelectorAll('input[form="blog-bulk"]:checked').length;
    const question = {
        publish: `Publish ${ticked} post(s)?`,
        draft: `Set ${ticked} post(s) to Draft? They go off the website.`,
        trash: `Move ${ticked} post(s) to the trash? They go off the website.`,
        untrash: `Restore ${ticked} post(s)?`,
        purge: `Delete ${ticked} post(s) for good? This cannot be undone.`,
    }[action.value];
    if (question && !window.confirm(question)) {
        event.preventDefault();
    }
});
JS);
$views = BlogPostView::counts(array_map('intval', $dataProvider->getKeys()));
?>
<div class="blog-post-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Posts for the public blog at /blog. Drafts are only visible to staff.', 'create', 'Write a post') ?>
        <div class="card-body pb-0">
            <nav class="nav nav-pills gap-1" aria-label="Posts or trash">
                <?= Html::a('Posts', ['index'], ['class' => 'nav-link' . ($inTrash ? '' : ' active'), 'aria-current' => $inTrash ? null : 'page']) ?>
                <?= Html::a('Trash <span class="badge bg-light text-dark">' . $trashCount . '</span>', ['index', 'trash' => 1], ['class' => 'nav-link' . ($inTrash ? ' active' : ''), 'aria-current' => $inTrash ? 'page' : null]) ?>
            </nav>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => $inTrash ? 'The trash is empty.' : 'No posts yet.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    [
                        'class' => CheckboxColumn::class,
                        // The boxes belong to the form under the list; the row buttons keep their own forms.
                        'checkboxOptions' => static fn(BlogPost $model): array => ['form' => 'blog-bulk', 'value' => $model->id, 'aria-label' => 'Select ' . $model->title],
                        'header' => Html::checkbox('selection_all', false, ['class' => 'select-on-check-all', 'aria-label' => 'Select all posts on this page']),
                    ],
                    ['attribute' => 'title', 'format' => 'raw', 'value' => static fn(BlogPost $model): string => Html::a(Html::encode($model->title), ['update', 'id' => $model->id], ['class' => 'fw-semibold'])
                        . Html::tag('div', '/blog/' . Html::encode($model->slug), ['class' => 'small text-body-secondary'])],
                    ['label' => 'Category', 'value' => static fn(BlogPost $model): string => $model->category->name ?? '-'],
                    [
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => static function (BlogPost $model): string {
                            $state = $model->stateLabel();

                            return Html::tag('span', $state, ['class' => 'badge ' . ['Draft' => 'bg-secondary', 'Scheduled' => 'bg-info text-dark', 'Published' => 'bg-success', 'In trash' => 'bg-danger'][$state]]);
                        },
                    ],
                    $inTrash
                        ? ['attribute' => 'deleted_at', 'format' => ['datetime', 'php:d M Y, H:i'], 'label' => 'Moved to trash']
                        : ['attribute' => 'published_at', 'format' => ['datetime', 'php:d M Y, H:i'], 'label' => 'Publish time'],
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
                    [
                        'label' => 'Actions',
                        'format' => 'raw',
                        'contentOptions' => ['class' => 'text-nowrap'],
                        'value' => static fn(BlogPost $model): string => $model->deleted_at !== null
                            ? GridActionHelper::button('restore', ['untrash', 'id' => $model->id], ['data' => ['method' => 'post']]) . ' '
                                . GridActionHelper::button('delete', ['delete', 'id' => $model->id], ['title' => 'Delete for good', 'data' => ['method' => 'post', 'confirm' => 'Delete this post for good? Its web address stops working, and this cannot be undone.']])
                            : GridActionHelper::button('view', ['preview', 'id' => $model->id], ['title' => 'Preview']) . ' '
                                . GridActionHelper::button('stats', ['stats', 'id' => $model->id]) . ' '
                                . GridActionHelper::button('copy', ['duplicate', 'id' => $model->id], ['data' => ['method' => 'post']]) . ' '
                                . GridActionHelper::button('update', ['update', 'id' => $model->id], ['title' => 'Edit']) . ' '
                                . GridActionHelper::button('delete', ['delete', 'id' => $model->id], ['title' => 'Move to trash', 'data' => ['method' => 'post', 'confirm' => 'Move this post to the trash? It goes off the website now; you can restore it within ' . BlogPost::TRASH_DAYS . ' days.']]),
                    ],
                ],
            ]) ?>
        </div>
        <?= Html::beginForm(['bulk'], 'post', ['id' => 'blog-bulk', 'class' => 'card-footer d-flex flex-wrap gap-2 align-items-center']) ?>
            <label for="bulk-action" class="mb-0">With ticked posts:</label>
            <?= Html::dropDownList('bulk_action', null, $inTrash ? ['untrash' => 'Restore', 'purge' => 'Delete for good'] : ['publish' => 'Publish', 'draft' => 'Set to Draft', 'trash' => 'Move to trash'], ['id' => 'bulk-action', 'class' => 'form-select form-select-sm w-auto', 'prompt' => 'Choose…']) ?>
            <?= Html::submitButton('Apply', ['class' => 'btn btn-sm btn-primary']) ?>
        <?= Html::endForm() ?>
    </div>
</div>
