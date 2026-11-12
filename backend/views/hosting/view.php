<?php

declare(strict_types=1);

use common\models\Hosting;
use yii\helpers\Html;

$this->title = $model->primary_domain ?: 'Hosting #' . $model->id;

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;

$statuses = Hosting::getStatuses();

$status = (int) $model->status;

$statusLabel = $statuses[$status] ?? 'Unknown';

$statusClass = match ($status) {
    Hosting::STATUS_ACTIVE => 'bg-success',
    Hosting::STATUS_INACTIVE => 'bg-secondary',
    Hosting::STATUS_SUSPENDED => 'bg-warning text-dark',
    default => 'bg-dark',
};

$this->params['subtitle'] = 'Hosting account, server access and renewal details.';
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left mr-1"></i> Back', ['index'], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-edit mr-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']),
];
$this->params['help'] = [
    'Update' => 'Edit the account, including its expiry date, status, cPanel details and renewal prices. The one-time Next Renewal Price is only shown there.',
    'Show' => 'Next to a saved cPanel password, for Admins only: reveals the saved cPanel password. Other staff see "Saved (visible to Admins)".',
    'Current Expiry Date' => 'Moves forward automatically when the customer pays a renewal invoice. Active accounts with a price are invoiced a set number of days before it.',
    'Renewal & Billing' => 'Base Renewal Amount is the price of the first renewal billed here; later renewals continue from the last price billed. "Global setting" means the standard cycle or increase is used.',
    'Renewal Monitoring' => 'Lists this customer\'s hosting accounts by expiry date.',
    'To stop renewing' => 'Set the status to Inactive with Update, or use the Disable icon on the list.',
];
if ($status === Hosting::STATUS_SUSPENDED) {
    $this->params['help'][] = 'This account is Suspended / Expired: it is not invoiced or reminded automatically, and turns Active again when a renewal invoice for it is paid.';
} elseif ($status !== Hosting::STATUS_ACTIVE) {
    $this->params['help'][] = 'This account is not Active, so it is not invoiced or reminded automatically.';
} elseif ((float) $model->renewal_base_amount <= 0 && (float) $model->next_renewal_amount <= 0) {
    $this->params['help'][] = 'This account has no renewal price, so it is not invoiced automatically. Set one with Update.';
}
?>

<div class="hosting-view">


    <div class="row">

        <div class="col-lg-6 mb-3">

            <div class="card card-outline card-primary shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Hosting Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            Client
                        </dt>

                        <dd class="col-sm-8">

                            <?php if ($model->user !== null): ?>
                                <div class="fw-semibold">
                                    <?= Html::encode(
                                        $model->user->username
                                    ) ?>
                                </div>

                                <?php if (!empty($model->user->email)): ?>
                                    <div class="text-muted small">
                                        <?= Html::encode(
                                            $model->user->email
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            <?php else: ?>
                                <span class="text-muted">
                                    -
                                </span>

                            <?php endif; ?>

                        </dd>

                        <dt class="col-sm-4">
                            Primary Domain
                        </dt>

                        <dd class="col-sm-8">
                            <span class="fw-semibold">
                                <?= Html::encode(
                                    $model->primary_domain
                                ) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">
                            Status
                        </dt>

                        <dd class="col-sm-8">

                            <?= Html::tag(
                                'span',
                                Html::encode($statusLabel),
                                [
                                    'class' => 'badge ' . $statusClass,
                                ]
                            ) ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

        <div class="col-lg-6 mb-3">

            <div class="card card-outline card-info shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        cPanel Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            cPanel IP
                        </dt>

                        <dd class="col-sm-8">
                            <?= Html::encode(
                                $model->cpanel_ip ?: '-'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Username
                        </dt>

                        <dd class="col-sm-8">
                            <?= Html::encode(
                                $model->cpanel_username ?: '-'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Password
                        </dt>

                        <dd class="col-sm-8">

                            <?php $identity = Yii::$app->user->identity; ?>
                            <?php if (!$model->cpanel_password): ?>
                                <span class="text-muted">-</span>
                            <?php elseif ($identity instanceof \common\models\Admin && $identity->isAdmin()): ?>
                                <code id="cpanel-password">••••••••</code>
                                <button type="button" class="btn btn-xs btn-outline-primary ms-2" id="cpanel-password-show"
                                        data-url="<?= Html::encode(\yii\helpers\Url::to(['cpanel-password', 'id' => $model->id])) ?>">Show</button>
                                <?php $this->registerJs(<<<'JS'
$('#cpanel-password-show').on('click', function () {
    var button = $(this);
    $.post(button.data('url'), {[yii.getCsrfParam()]: yii.getCsrfToken()}).done(function (data) {
        $('#cpanel-password').text(data.password !== null ? data.password : data.error);
        button.remove();
    });
});
JS); ?>
                            <?php else: ?>
                                <span class="text-muted">Saved (visible to Admins)</span>
                            <?php endif; ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-7 mb-3">

            <div class="card card-outline card-success shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Renewal & Billing
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Current Expiry Date
                        </dt>

                        <dd class="col-sm-7">

                            <?= $model->current_expiry_date
                                ? Yii::$app->formatter->asDate(
                                    $model->current_expiry_date,
                                    'php:d M Y'
                                )
                                : '-' ?>

                        </dd>

                        <dt class="col-sm-5">
                            Base Renewal Amount
                        </dt>

                        <dd class="col-sm-7">

                            <?= $model->renewal_base_amount !== null
                                ? Yii::$app->formatter->asCurrency(
                                    $model->renewal_base_amount,
                                    'INR'
                                )
                                : '-' ?>

                        </dd>

                        <dt class="col-sm-5">
                            Renewal Cycle
                        </dt>

                        <dd class="col-sm-7">

                            <?php if ($model->renewal_cycle_months): ?>
                                <?= Html::encode(
                                    $model->renewal_cycle_months
                                    . ' month(s)'
                                ) ?>

                            <?php else: ?>
                                <span class="text-muted">
                                    <?= Html::encode(\common\models\BillingSettings::cycleLabel(\common\models\InvoiceItem::TYPE_HOSTING)) ?>
                                </span>

                            <?php endif; ?>

                        </dd>

                        <dt class="col-sm-5">
                            Renewal Increase
                        </dt>

                        <dd class="col-sm-7">

                            <?php if (
                                $model->renewal_increase_percent !== null
): ?>
                                <?= Html::encode(
                                    number_format(
                                        (float) $model->renewal_increase_percent,
                                        2
                                    )
                                    . '%'
                                ) ?>

                            <?php else: ?>
                                <span class="text-muted">
                                    Global setting
                                </span>

                            <?php endif; ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

        <div class="col-lg-5 mb-3">

            <div class="card card-outline card-secondary shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        System Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Record ID
                        </dt>

                        <dd class="col-sm-7">
                            <?= (int) $model->id ?>
                        </dd>

                        <dt class="col-sm-5">
                            Created At
                        </dt>

                        <dd class="col-sm-7">

                            <?= $model->created_at
                                ? Yii::$app->formatter->asDatetime(
                                    $model->created_at,
                                    'php:d M Y, h:i A'
                                )
                                : '-' ?>

                        </dd>

                        <dt class="col-sm-5">
                            Updated At
                        </dt>

                        <dd class="col-sm-7">

                            <?= $model->updated_at
                                ? Yii::$app->formatter->asDatetime(
                                    $model->updated_at,
                                    'php:d M Y, h:i A'
                                )
                                : '-' ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex gap-2">

        <?= Html::a(
            '<i class="fas fa-calendar-check mr-1"></i> Renewal Monitoring',
            [
                '/renewal-monitoring/index',
                'type' => 'hosting',
                'user_id' => $model->user_id,
                'window' => 'all',
            ],
            [
                'class' => 'btn btn-outline-info',
            ]
        ) ?>

    </div>

</div>