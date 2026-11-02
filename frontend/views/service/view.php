<?php

declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;

$this->title = 'Service Details';

$formatter = Yii::$app->formatter;

$serviceName = match ($type) {
    'domain' => $model->domain_url,
    'hosting' => $model->primary_domain,
    'maintenance' => $model->website_url,
    default => 'Service',
};

$isActive = (int) $model->status === 1;
$statusClass = match ((int) $model->status) {
    1 => 'bg-success-subtle text-success-emphasis',
    0 => 'bg-danger-subtle text-danger-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};
$today = new \DateTimeImmutable('today');
$expiryDate = new \DateTimeImmutable($model->current_expiry_date);
$daysRemaining = (int) $today->diff($expiryDate)->format('%r%a');
[$expiryBadge, $expiryText] = match (true) {
    $daysRemaining < 0 => ['bg-danger-subtle text-danger-emphasis', 'Expired'],
    $daysRemaining === 0 => ['bg-danger-subtle text-danger-emphasis', 'Expires today'],
    $daysRemaining <= 15 => ['bg-warning-subtle text-warning-emphasis', "{$daysRemaining} day" . ($daysRemaining === 1 ? '' : 's') . ' left'],
    default => ['bg-success-subtle text-success-emphasis', "{$daysRemaining} days left"],
};
$invoiceClass = static fn(?string $status): string => match ($status) {
    Invoice::STATUS_PAID => 'bg-success-subtle text-success-emphasis',
    Invoice::STATUS_PARTIALLY_PAID => 'bg-warning-subtle text-warning-emphasis',
    Invoice::STATUS_UNPAID => 'bg-danger-subtle text-danger-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};
$eventClass = static fn(?string $status): string => match ($status) {
    'Pending' => 'bg-warning-subtle text-warning-emphasis',
    'Invoiced' => 'bg-info-subtle text-info-emphasis',
    'Fulfilled' => 'bg-success-subtle text-success-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div class="min-w-0">
        <div class="small text-uppercase text-body-secondary fw-semibold mb-1"><?= Html::encode(ucfirst($type)) ?></div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2 flex-wrap">
            <span class="text-break"><?= Html::encode($serviceName) ?></span>
            <span class="badge fs-6 fw-semibold <?= $statusClass ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
        </h1>
        <div class="text-body-secondary">Customer Service Information</div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <?= Html::a('View All Services', ['/service/index'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('View Invoices', ['/invoice/index'], ['class' => 'btn btn-outline-primary']) ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Service Information</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="small text-body-secondary">Service Type</div>
                        <div class="fw-semibold"><?= Html::encode(ucfirst($type)) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-body-secondary">Expiry Date</div>
                        <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                            <?= $formatter->asDate($model->current_expiry_date, 'php:d M Y') ?>
                            <span class="badge fw-semibold <?= $expiryBadge ?>"><?= $expiryText ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="small text-body-secondary">Service Name</div>
                        <div class="fw-semibold text-break"><?= Html::encode($serviceName) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($latestEvent): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent py-3">
                    <h2 class="h6 fw-bold mb-0">Latest Billing Activity</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-body-secondary">Description</div>
                            <div class="fw-semibold text-break"><?= Html::encode($latestEvent->description) ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-body-secondary">Status</div>
                            <div><?= Html::tag('span', Html::encode($latestEvent->status), ['class' => 'badge ' . $eventClass($latestEvent->status)]) ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-body-secondary">Period</div>
                            <div>
                                <span class="text-nowrap"><?= $formatter->asDate($latestEvent->period_start, 'php:d M Y') ?></span>
                                -
                                <span class="text-nowrap"><?= $formatter->asDate($latestEvent->period_end, 'php:d M Y') ?></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-body-secondary">Due Date</div>
                            <div class="text-nowrap"><?= $formatter->asDate($latestEvent->due_date, 'php:d M Y') ?></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-body-secondary">Amount</div>
                            <div class="fw-bold text-nowrap"><?= $formatter->asCurrency($latestEvent->amount, 'INR') ?></div>
                        </div>
                        <?php if ($latestEvent->previous_amount): ?>
                            <div class="col-6 col-md-4">
                                <div class="small text-body-secondary">Previous Amount</div>
                                <div class="text-nowrap"><?= $formatter->asCurrency($latestEvent->previous_amount, 'INR') ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($latestEvent->increase_percent > 0): ?>
                            <div class="col-6 col-md-4">
                                <div class="small text-body-secondary">Increase</div>
                                <div><?= $formatter->asDecimal($latestEvent->increase_percent, 2) ?>%</div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($latestEvent->notes)): ?>
                            <div class="col-12">
                                <div class="small text-body-secondary">Notes</div>
                                <div class="text-break"><?= Html::encode($latestEvent->notes) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Related Invoice</h2>
            </div>
            <?php if ($invoice): ?>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 fw-normal text-body-secondary">Invoice Number</dt>
                        <dd class="col-6 text-end fw-semibold text-break"><?= Html::a(Html::encode($invoice->invoice_number), ['/invoice/view', 'number' => $invoice->invoice_number], ['class' => 'text-decoration-none']) ?></dd>
                        <dt class="col-6 fw-normal text-body-secondary">Total Amount</dt>
                        <dd class="col-6 text-end fw-bold"><?= $formatter->asCurrency($invoice->total_payable, 'INR') ?></dd>
                        <dt class="col-6 fw-normal text-body-secondary">Status</dt>
                        <dd class="col-6 text-end mb-0">
                            <span class="badge <?= $invoiceClass($invoice->status) ?>"><?= Html::encode(Invoice::getStatuses()[$invoice->status] ?? $invoice->status) ?></span>
                        </dd>
                    </dl>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="fas fa-file-invoice fa-2x mb-2 opacity-50"></i>
                    <div>No related invoice found.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
