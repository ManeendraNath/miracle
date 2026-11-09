<?php

declare(strict_types=1);

use common\models\Coupon;
use common\models\CouponRedemption;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Coupon $model */

$this->title = $model->code;
$this->params['breadcrumbs'][] = ['label' => 'Promotional Coupon', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$active = $model->is_active === Coupon::IS_ACTIVE_Y;
$this->params['subtitle'] = Html::encode($model->name) . ' '
    . Html::tag('span', Html::encode($model->getStatusLabel()), ['class' => $active ? 'badge bg-success ms-1' : 'badge bg-secondary ms-1']);
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']),
];

$formatter = Yii::$app->formatter;
$used = (int) CouponRedemption::find()->where(['coupon_id' => $model->id])->count();
$limit = (int) $model->uses_per_coupon;
/** @var \common\models\base\User[] $assigned */
$assigned = $model->getUsers()->orderBy('username')->all();
?>

<div class="coupon-view">
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-success shadow-sm h-100">
                <div class="card-header"><h3 class="card-title">Discount</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Discount</dt>
                        <dd class="col-sm-7 fw-semibold"><?= Html::encode($model->getDiscountLabel()) ?> <span class="text-muted fw-normal">(<?= Html::encode($model->getTypeLabel()) ?>)</span></dd>
                        <dt class="col-sm-5">Minimum Order</dt>
                        <dd class="col-sm-7"><?= (float) $model->minimum_order > 0 ? $formatter->asCurrency($model->minimum_order, 'INR') : 'None' ?></dd>
                        <dt class="col-sm-5">Applies To</dt>
                        <dd class="col-sm-7"><?= Html::encode(implode(', ', $model->getAppliesToLabels()) ?: 'All services') ?></dd>
                        <dt class="col-sm-5">Customers</dt>
                        <dd class="col-sm-7 mb-0">
                            <?= $assigned ? implode('<br>', array_map(static fn ($u): string => Html::a(Html::encode($u->username), ['/user/view', 'id' => $u->id]), $assigned)) : 'Any customer' ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-info shadow-sm h-100">
                <div class="card-header"><h3 class="card-title">Validity &amp; Usage</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Starts</dt>
                        <dd class="col-sm-7"><?= $model->start_date ? $formatter->asDatetime(strtotime($model->start_date), 'php:d M Y, h:i A') : 'Immediately' ?></dd>
                        <dt class="col-sm-5">Expires</dt>
                        <dd class="col-sm-7"><?= $model->end_date ? $formatter->asDatetime(strtotime($model->end_date), 'php:d M Y, h:i A') : 'No expiry' ?></dd>
                        <dt class="col-sm-5">Used</dt>
                        <dd class="col-sm-7"><?= $used ?> <?= $limit > 0 ? "of {$limit}" : 'times (no limit)' ?></dd>
                        <dt class="col-sm-5">Per Customer</dt>
                        <dd class="col-sm-7 mb-0"><?= (int) $model->uses_per_user > 0 ? (int) $model->uses_per_user . ' time(s)' : 'No limit' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <?php if (trim((string) $model->description) !== ''): ?>
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header"><h3 class="card-title">Description</h3></div>
            <div class="card-body"><?= nl2br(Html::encode($model->description)) ?></div>
        </div>
    <?php endif; ?>

    <div class="text-muted small mb-3">
        Record #<?= (int) $model->id ?><?= $model->created_at ? ' · created ' . $formatter->asDatetime($model->created_at, 'php:d M Y, h:i A') : '' ?>
    </div>
</div>
