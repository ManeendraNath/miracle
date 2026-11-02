<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Notifications';

$notifications = $dataProvider->getModels();
$formatter = Yii::$app->formatter;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Notifications</h1>
        <div class="text-body-secondary">Updates about your invoices, services and support requests.</div>
    </div>
    <?= Html::beginForm(['/notification/mark-all-read'], 'post') ?>
        <?= Html::submitButton('<i class="fas fa-check-double me-1"></i> Mark All Read', ['class' => 'btn btn-outline-primary']) ?>
    <?= Html::endForm() ?>
</div>

<div class="card border-0 shadow-sm">
    <?php if (!$notifications): ?>
        <div class="text-center py-5 text-body-secondary">
            <i class="fas fa-bell-slash fa-2x mb-2 opacity-50"></i>
            <div>No notifications yet.</div>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush rounded">
            <?php foreach ($notifications as $notification): ?>
                <?php $unread = !$notification->is_read; ?>
                <a href="<?= \yii\helpers\Url::to(['/notification/view', 'id' => $notification->id]) ?>"
                   class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3<?= $unread ? ' bg-body-tertiary' : '' ?>"
                   <?= $unread ? 'style="box-shadow: inset 4px 0 0 var(--bs-primary)"' : '' ?>>
                    <span class="dash-stat-icon <?= $unread ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' ?>">
                        <i class="fas fa-bell"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="text-break <?= $unread ? 'fw-bold' : 'fw-normal' ?>"><?= Html::encode($notification->title) ?></div>
                        <div class="small text-body-secondary"><?= $formatter->asDatetime($notification->created_at) ?></div>
                    </div>
                    <?php if ($unread): ?>
                        <span class="badge rounded-pill bg-primary">New</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($dataProvider->pagination->pageCount > 1): ?>
        <div class="d-flex justify-content-end px-3 py-3 border-top">
            <?= \yii\bootstrap5\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'mb-0'],
                'listOptions' => ['class' => 'pagination pagination-sm mb-0'],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
