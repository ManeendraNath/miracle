<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\BlogComment;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $filter */
/** @var array<int, string> $counts */

$this->title = 'Blog Comments';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Waiting' => 'New comments from readers. They show on the post only once you approve them. Staff get an alert when nothing else is waiting; the count next to Comments in the menu shows the rest.',
    'Approve' => 'Shows the comment on the post.',
    'Reply' => 'Write an answer shown under the comment with your author name (My account) and a Team label. Replying approves the comment too.',
    'Spam' => 'Hides it. Comments that look like spam (many links) or that were sent the way bots send are put here by themselves, without an alert; look here now and then for real ones. Spam older than ' . BlogComment::SPAM_DAYS . ' days is deleted by itself every week.',
    'Trash icon' => 'Delete the comment and its replies for good.',
    'Limits' => 'One address can send ' . BlogComment::RATE_LIMIT . ' comments in ' . BlogComment::RATE_MINUTES . ' minutes. Comments can be closed per post with Allow comments on the post form; those already shown stay.',
];
GridViewHelper::register($this);
$tabs = [BlogComment::STATUS_PENDING => 'Waiting', BlogComment::STATUS_APPROVED => 'Shown', BlogComment::STATUS_SPAM => 'Spam'];
?>
<div class="blog-comment-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Readers\' comments on blog posts. Nothing shows on the website until you approve it.') ?>
        <div class="card-body pb-0">
            <nav class="nav nav-pills gap-1" aria-label="Filter comments">
                <?php foreach ($tabs as $status => $label): ?>
                    <?= Html::a(Html::encode($label) . ' <span class="badge bg-light text-dark">' . (int) ($counts[$status] ?? 0) . '</span>', ['index', 'status' => $status], ['class' => 'nav-link' . ($filter === $status ? ' active' : ''), 'aria-current' => $filter === $status ? 'page' : null]) ?>
                <?php endforeach; ?>
                <?= Html::a('All', ['index', 'status' => 'all'], ['class' => 'nav-link' . ($filter === null ? ' active' : ''), 'aria-current' => $filter === null ? 'page' : null]) ?>
            </nav>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => 'No comments here.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    [
                        'label' => 'Comment',
                        'format' => 'raw',
                        'value' => static function (BlogComment $model): string {
                            $replies = '';
                            foreach ($model->replies as $reply) {
                                $replies .= '<div class="small border-start ps-2 mt-2"><strong>' . Html::encode($reply->author_name) . ':</strong> ' . nl2br(Html::encode($reply->body)) . '</div>';
                            }

                            return '<div style="max-width: 40rem; overflow-wrap: anywhere">' . nl2br(Html::encode($model->body)) . $replies . '</div>';
                        },
                    ],
                    [
                        'label' => 'From',
                        'format' => 'raw',
                        'value' => static fn(BlogComment $model): string => Html::encode($model->author_name)
                            . '<br><span class="small text-muted">' . Html::encode((string) $model->author_email) . '</span>',
                    ],
                    [
                        'label' => 'Post',
                        'format' => 'raw',
                        'value' => static fn(BlogComment $model): string => Html::a(Html::encode($model->post->title), ['/blog-post/preview', 'id' => $model->post_id], ['target' => '_blank', 'rel' => 'noopener']),
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Sent',
                        'format' => ['datetime', 'php:d M Y, H:i'],
                    ],
                    [
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => static fn(BlogComment $model): string => Html::tag('span', Html::encode(BlogComment::statuses()[$model->status] ?? '?'), [
                            'class' => 'badge ' . match ($model->status) {
                                BlogComment::STATUS_APPROVED => 'bg-success',
                                BlogComment::STATUS_SPAM => 'bg-danger',
                                default => 'bg-warning text-dark',
                            },
                        ]),
                    ],
                    [
                        'label' => 'Actions',
                        'format' => 'raw',
                        'contentOptions' => ['class' => 'text-nowrap'],
                        'value' => static function (BlogComment $model): string {
                            $buttons = [];
                            if ($model->status !== BlogComment::STATUS_APPROVED) {
                                $buttons[] = Html::a('Approve', ['approve', 'id' => $model->id], ['class' => 'btn btn-sm btn-success', 'data-method' => 'post']);
                            }
                            $buttons[] = Html::a('Reply', ['reply', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']);
                            if ($model->status !== BlogComment::STATUS_SPAM) {
                                $buttons[] = Html::a('Spam', ['spam', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary', 'data-method' => 'post']);
                            }
                            $buttons[] = Html::a('<i class="fas fa-trash" aria-hidden="true"></i>', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline-danger', 'title' => 'Delete', 'aria-label' => 'Delete',
                                'data-method' => 'post', 'data-confirm' => 'Delete this comment and its replies for good?',
                            ]);

                            return implode(' ', $buttons);
                        },
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
