<?php
declare(strict_types=1);

use common\models\Coupon;
use yii\helpers\Html;

$this->title = $model->code;

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$active = $model->is_active === Coupon::IS_ACTIVE_Y;
?>

<div class="coupon-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><?= Html::encode($model->code) ?></h4>
            <div class="text-muted small"><?= Html::encode($model->name) ?></div>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="fas fa-arrow-left mr-1"></i> Back', ['index'], ['class' => 'btn btn-light border']) ?>
            <?= Html::a('<i class="fas fa-edit mr-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card card-outline card-primary shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">Coupon Information</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Coupon Code</dt>
                        <dd class="col-sm-8">
                            <code class="fw-semibold"><?= Html::encode($model->code) ?></code>
                        </dd>
                        <dt class="col-sm-4">Campaign</dt>
                        <dd class="col-sm-8"><?= Html::encode($model->name) ?></dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <?= Html::tag('span', Html::encode($model->getStatusLabel()), ['class' => $active ? 'badge bg-success' : 'badge bg-secondary']) ?>
                        </dd>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($model->description)): ?>
                                <?= nl2br(Html::encode($model->description)) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <div class="card card-outline card-success shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">Discount Rules</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Type</dt>
                        <dd class="col-sm-7"><?= Html::encode($model->getTypeLabel()) ?></dd>
                        <dt class="col-sm-5">Discount</dt>
                        <dd class="col-sm-7 fw-semibold">
                            <?= Html::encode($model->getDiscountLabel()) ?>
                        </dd>
                        <dt class="col-sm-5">Minimum Order</dt>
                        <dd class="col-sm-7">
                            <?= Yii::$app->formatter->asCurrency($model->minimum_order, 'INR') ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-info shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">Validity Period</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Starts</dt>
                        <dd class="col-sm-8">
                            <?= $model->start_date ? Yii::$app->formatter->asDatetime($model->start_date, 'php:d M Y, h:i A') : 'Immediately' ?>
                        </dd>
                        <dt class="col-sm-4">Expires</dt>
                        <dd class="col-sm-8">
                            <?= $model->end_date ? Yii::$app->formatter->asDatetime($model->end_date, 'php:d M Y, h:i A') : 'No expiry' ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-secondary shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">Usage Limits</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Usage Limit</dt>
                        <dd class="col-sm-6"><?= (int) $model->uses_per_coupon ?></dd>
                        <dt class="col-sm-6">Per Customer</dt>
                        <dd class="col-sm-6"><?= (int) $model->uses_per_user > 0 ? (int) $model->uses_per_user : 'No limit' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header">
            <h3 class="card-title">System Information</h3>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-lg-2 col-sm-4">Record ID</dt>
                <dd class="col-lg-4 col-sm-8"><?= (int) $model->id ?></dd>
                <dt class="col-lg-2 col-sm-4">Created</dt>
                <dd class="col-lg-4 col-sm-8">
                    <?= $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A') : '-' ?>
                </dd>
            </dl>
        </div>
    </div>
</div>