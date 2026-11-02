<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var common\models\CustomerNotification $model */
$this->title = $model->title;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-3 mb-4">
    <div class="min-w-0">
        <h1 class="h3 fw-bold mb-1 text-break"><?= Html::encode($model->title) ?></h1>
        <div class="text-body-secondary"><i class="far fa-clock me-1"></i><?= Yii::$app->formatter->asDatetime($model->created_at) ?></div>
    </div>
    <?= Html::a('<i class="fas fa-arrow-left me-1"></i> All notifications', ['/notification/index'], ['class' => 'btn btn-outline-secondary text-nowrap']) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="text-break"><?= nl2br(Html::encode($model->message)) ?></div>

        <?php if (!empty($model->route)): ?>
            <div class="mt-4">
                <?= Html::a('Open Related Item <i class="fas fa-arrow-right ms-1"></i>', $model->route, ['class' => 'btn btn-primary']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
