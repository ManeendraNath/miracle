<?php

declare(strict_types=1);

use common\models\BlogComment;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $model */
/** @var array<string, string> $days day (Y-m-d) => views */
/** @var array{total: int, recent: int} $views */
/** @var array<int, string> $comments status => count */
/** @var int $emailed */
/** @var string[] $redirects */

$this->title = 'Stats: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Blog Posts', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Stats';
$this->params['help'] = [
    'Views' => 'Readers on the website: each visitor counts once a day. Search engines, link previews and staff previews are not counted.',
    'Comments' => 'Reader comments by status; replies by staff are not counted.',
    'Newsletter' => 'Subscribers the post was emailed to.',
    'Old addresses' => 'Earlier web addresses of this post. They send readers and search engines to its current address.',
];
$max = max(1, ...array_map('intval', $days ?: [0]));
?>
<div class="card card-outline card-primary shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3"><?= Html::encode($model->title) ?> <span class="badge bg-secondary"><?= Html::encode($model->stateLabel()) ?></span></h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="border rounded p-3"><div class="small text-body-secondary">Views, all time</div><div class="fs-4 fw-semibold"><?= number_format($views['total']) ?></div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-3"><div class="small text-body-secondary">Views, last 30 days</div><div class="fs-4 fw-semibold"><?= number_format($views['recent']) ?></div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-3"><div class="small text-body-secondary">Comments shown / waiting</div><div class="fs-4 fw-semibold"><?= (int) ($comments[BlogComment::STATUS_APPROVED] ?? 0) ?> / <?= (int) ($comments[BlogComment::STATUS_PENDING] ?? 0) ?></div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-3"><div class="small text-body-secondary">Emailed to subscribers</div><div class="fs-4 fw-semibold"><?= number_format($emailed) ?></div></div></div>
        </div>

        <h3 class="h6">Views a day, last 30 days</h3>
        <table class="table table-sm align-middle mb-4" style="max-width: 40rem">
            <caption class="visually-hidden">Views per day for the last 30 days, newest first</caption>
            <thead><tr><th scope="col">Day</th><th scope="col" class="text-end">Views</th><th scope="col"><span class="visually-hidden">Bar</span></th></tr></thead>
            <tbody>
            <?php for ($i = 0; $i < 30; $i++): ?>
                <?php
                $day = date('Y-m-d', strtotime("-$i days"));
                $n = (int) ($days[$day] ?? 0);
                ?>
                <tr>
                    <td class="text-nowrap"><?= Yii::$app->formatter->asDate($day, 'php:D d M') ?></td>
                    <td class="text-end"><?= $n ?></td>
                    <td style="width: 60%"><div class="bg-primary rounded" style="height: .6rem; width: <?= round($n / $max * 100, 1) ?>%" aria-hidden="true"></div></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>

        <?php if ($redirects): ?>
            <h3 class="h6">Old addresses that lead here</h3>
            <ul class="small">
                <?php foreach ($redirects as $slug): ?>
                    <li>/blog/<?= Html::encode($slug) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="mb-0"><?= Html::a('Edit the post', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?> <?= Html::a('Back to posts', ['index'], ['class' => 'btn btn-sm btn-link']) ?></p>
    </div>
</div>
