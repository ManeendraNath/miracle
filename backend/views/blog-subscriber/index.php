<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\BlogSubscriber;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $filter */
/** @var string $search */
/** @var array<int, string> $counts */

$this->title = 'Newsletter Subscribers';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Subscribed' => 'Confirmed their address from the email we sent. They get each new post by email once it is live, unless the post form\'s "Email it to subscribers" box is unticked or the post is kept out of search engines.',
    'Not confirmed' => 'Signed up but have not clicked the link in the confirmation email yet. They get nothing until they do.',
    'Unsubscribed' => 'Used the unsubscribe link. They get nothing; signing up again sends a new confirmation email.',
    'Sending' => 'The Blog newsletter cron job sends up to ' . (int) (Yii::$app->params['newsletterBatch'] ?? 40) . ' emails every 15 minutes, so a large list takes a while.',
    'Trash icon' => 'Delete the address for good, e.g. when someone asks you to forget it. To just stop emails, they can unsubscribe.',
];
GridViewHelper::register($this);
$tabs = [BlogSubscriber::STATUS_SUBSCRIBED => 'Subscribed', BlogSubscriber::STATUS_PENDING => 'Not confirmed', BlogSubscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed'];
?>
<div class="blog-subscriber-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Readers who get new blog posts by email. They sign up on the blog and confirm by email.') ?>
        <div class="card-body pb-0 d-flex flex-wrap gap-2 align-items-center">
            <nav class="nav nav-pills gap-1" aria-label="Filter subscribers">
                <?= Html::a('All', ['index'], ['class' => 'nav-link' . ($filter === null ? ' active' : ''), 'aria-current' => $filter === null ? 'page' : null]) ?>
                <?php foreach ($tabs as $status => $label): ?>
                    <?= Html::a(Html::encode($label) . ' <span class="badge bg-light text-dark">' . (int) ($counts[$status] ?? 0) . '</span>', ['index', 'status' => $status], ['class' => 'nav-link' . ($filter === $status ? ' active' : ''), 'aria-current' => $filter === $status ? 'page' : null]) ?>
                <?php endforeach; ?>
            </nav>
            <form method="get" class="ms-auto d-flex gap-1" role="search">
                <?php if ($filter !== null):
                    ?><input type="hidden" name="status" value="<?= $filter ?>"><?php
                endif; ?>
                <label for="subscriber-q" class="visually-hidden">Search by email</label>
                <input type="search" id="subscriber-q" name="q" value="<?= Html::encode($search) ?>" class="form-control form-control-sm" placeholder="Search by email">
                <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
            </form>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'emptyText' => $filter !== null || $search !== '' ? 'No subscribers match this filter.' : 'No subscribers yet.',
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    'email:email',
                    [
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => static fn(BlogSubscriber $model): string => Html::tag('span', Html::encode(BlogSubscriber::statuses()[$model->status] ?? '?'), [
                            'class' => 'badge ' . match ($model->status) {
                                BlogSubscriber::STATUS_SUBSCRIBED => 'bg-success',
                                BlogSubscriber::STATUS_UNSUBSCRIBED => 'bg-secondary',
                                default => 'bg-warning text-dark',
                            },
                        ]),
                    ],
                    ['attribute' => 'created_at', 'label' => 'Signed up', 'format' => ['datetime', 'php:d M Y, H:i']],
                    ['attribute' => 'confirmed_at', 'label' => 'Confirmed', 'format' => ['datetime', 'php:d M Y, H:i']],
                    ['attribute' => 'unsubscribed_at', 'label' => 'Unsubscribed', 'format' => ['datetime', 'php:d M Y, H:i']],
                    [
                        'label' => 'Actions',
                        'format' => 'raw',
                        'value' => static fn(BlogSubscriber $model): string => Html::a('<i class="fas fa-trash" aria-hidden="true"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-danger', 'title' => 'Delete', 'aria-label' => 'Delete ' . $model->email,
                            'data-method' => 'post', 'data-confirm' => 'Delete ' . $model->email . ' for good?',
                        ]),
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
