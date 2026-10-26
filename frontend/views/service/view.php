<?php
declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;

$this->title = 'Service Details';

$serviceName = match ($type) {
    'domain' => $model->domain_url,
    'hosting' => $model->primary_domain,
    'maintenance' => $model->website_url,
    default => 'Service',
};

$statusClass = match ((int) $model->status) {
    1 => 'bg-success',
    0 => 'bg-danger',
    default => 'bg-secondary',
};
$today = new \DateTimeImmutable(date("Y-m-d H:i:s"));
$expiryDate = new \DateTimeImmutable($model->current_expiry_date);
$daysRemaining = (int) $today->diff($expiryDate)->format('%r%a');
$expiryBadge = match (true) {
    $daysRemaining < 0 => 'bg-danger',
    $daysRemaining <= 15 => 'bg-warning text-dark',
    default => 'bg-success',
};
?>

<div class="service-view">

    <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg, #0b1f4d, #1565c0); color:#fff;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="text-uppercase small text-white-50 mb-2">
                        <?= Html::encode(ucfirst($type)) ?>
                    </div>
                    <h2 class="mb-2">
                        <?= Html::encode($serviceName) ?>
                    </h2>
                    <div class="opacity-75">
                        Customer Service Information
                    </div>
                </div>

                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge <?= $statusClass ?> fs-6">
                        <?= (int) $model->status === 1 ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <strong>Service Information</strong>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small">
                                Service Type
                            </div>
                            <div class="fw-semibold">
                                <?= Html::encode(ucfirst($type)) ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small">
                                Expiry Date
                            </div>

                            <div class="fw-semibold">
                                <?= Yii::$app->formatter->asDate($model->current_expiry_date, 'php:d M Y') ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">
                                Service Name
                            </div>
                            <div class="fw-semibold">
                                <?= Html::encode($serviceName) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($latestEvent): ?>
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header">
                        <strong>Latest Billing Activity</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="text-muted small">
                                    Description
                                </div>
                                <div class="fw-semibold">
                                    <?= Html::encode($latestEvent->description) ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Status
                                </div>
                                <div>
                                    <?php
                                    $statusClass = match ($latestEvent->status) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Invoiced' => 'bg-primary',
                                        'Fulfilled' => 'bg-success',
                                        'Cancelled' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };

                                    echo Html::tag('span', $latestEvent->status, ['class' => 'badge ' . $statusClass]);
                                    ?>

                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Period
                                </div>
                                <div>
                                    <?= Yii::$app->formatter->asDate($latestEvent->period_start, 'php:d M Y') ?>
                                    -
                                    <?= Yii::$app->formatter->asDate($latestEvent->period_end, 'php:d M Y') ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Due Date
                                </div>
                                <div>
                                    <?= Yii::$app->formatter->asDate($latestEvent->due_date, 'php:d M Y') ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Amount
                                </div>
                                <div class="fw-bold">
                                    <?= Yii::$app->formatter->asCurrency($latestEvent->amount, 'INR') ?>
                                </div>
                            </div>
                            <?php if ($latestEvent->previous_amount): ?>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Previous Amount
                                    </div>
                                    <div>
                                        <?= Yii::$app->formatter->asCurrency($latestEvent->previous_amount, 'INR') ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($latestEvent->increase_percent > 0): ?>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Increase
                                    </div>
                                    <div>
                                        <?= Yii::$app->formatter->asDecimal($latestEvent->increase_percent, 2) ?>%
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($latestEvent->notes)): ?>
                                <div class="col-md-12">
                                    <div class="text-muted small">
                                        Notes
                                    </div>
                                    <div>
                                        <?= Html::encode($latestEvent->notes) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <strong>Related Invoice</strong>
                </div>
                <div class="card-body">
                    <?php if ($invoice): ?>
                        <div class="text-muted small">
                            Invoice Number
                        </div>
                        <div class="fw-semibold mb-3">
                            <?= Html::a(Html::encode($invoice->invoice_number), ['/invoice/view', 'number' => $invoice->invoice_number]) ?>
                        </div>
                        <div class="text-muted small">
                            Total Amount
                        </div>
                        <div class="fw-bold mb-3">
                            <?= Yii::$app->formatter->asCurrency($invoice->total_payable, 'INR') ?>
                        </div>
                        <div class="text-muted small">
                            Status
                        </div>
                        <div>
                            <?= Html::encode(Invoice::getStatuses()[$invoice->status] ?? $invoice->status) ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">
                            No related invoice found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <?= Html::a('View All Services', ['/service/index'], ['class' => 'btn btn-primary w-100 mb-2']) ?>
                    <?= Html::a('View Invoices', ['/invoice/index'], ['class' => 'btn btn-outline-primary w-100']) ?>
                </div>
            </div>
        </div>
    </div>
</div>