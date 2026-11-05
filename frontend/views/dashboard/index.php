<?php

declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Customer Portal';
$today = new \DateTimeImmutable('today');
$formatter = Yii::$app->formatter;
$serviceCount = $domainCount + $hostingCount + $maintenanceCount;

// Only what needs the customer's action, each linking to where they act on it.
$attention = array_filter([
    $openInvoices ? Html::a("{$openInvoices} unpaid invoice" . ($openInvoices > 1 ? 's' : ''), ['/invoice/index']) : null,
    $pendingPayments ? Html::a("{$pendingPayments} payment" . ($pendingPayments > 1 ? 's' : '') . ' under review', ['/payment/index']) : null,
    $openSupportTickets ? Html::a("{$openSupportTickets} open support ticket" . ($openSupportTickets > 1 ? 's' : ''), ['/support/index']) : null,
    $unreadNotifications ? Html::a("{$unreadNotifications} unread notification" . ($unreadNotifications > 1 ? 's' : ''), ['/notification/index']) : null,
]);

$stats = [
    ['label' => 'Active Services', 'value' => $serviceCount, 'icon' => 'fas fa-cubes', 'color' => 'primary', 'url' => ['/service/index'],
        'note' => "{$domainCount} domain · {$hostingCount} hosting · {$maintenanceCount} maint."],
    ['label' => 'Invoices Due', 'value' => $openInvoices, 'icon' => 'fas fa-file-invoice', 'color' => $openInvoices ? 'danger' : 'success', 'url' => ['/invoice/index'],
        'note' => "{$totalInvoices} invoice" . ($totalInvoices === 1 ? '' : 's') . ' in total'],
    ['label' => 'Renewals (60 days)', 'value' => $renewalCount, 'icon' => 'fas fa-sync-alt', 'color' => $renewalCount ? 'warning' : 'success', 'url' => ['/renewal/index'],
        'note' => $renewalCount ? 'Renew to avoid downtime' : 'Nothing due soon'],
    ['label' => 'Open Tickets', 'value' => $openSupportTickets, 'icon' => 'fas fa-headset', 'color' => 'info', 'url' => ['/support/index'],
        'note' => 'Open or in progress'],
];

$invoiceBadge = static fn(string $status): string => match ($status) {
    Invoice::STATUS_PAID => 'bg-success',
    Invoice::STATUS_UNPAID => 'bg-danger',
    Invoice::STATUS_PARTIALLY_PAID => 'bg-warning text-dark',
    default => 'bg-secondary',
};
?>

<div class="dash-hero card border-0 shadow-sm mb-4">
    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
        <div class="flex-grow-1">
            <div class="small text-uppercase text-white-50 fw-semibold mb-1">Customer Portal</div>
            <h1 class="h3 fw-bold mb-1">Welcome back, <?= Html::encode(Yii::$app->user->identity->username) ?></h1>
            <p class="mb-0 text-white-50">Your services, invoices and support requests in one place.</p>
        </div>
        <div class="text-md-end">
            <div class="small text-white-50">Outstanding balance</div>
            <div class="h2 fw-bold mb-2"><?= $formatter->asCurrency($outstandingTotal, 'INR') ?></div>
            <?php if ($outstandingTotal > 0): ?>
                <?= Html::a('<i class="fas fa-credit-card me-1"></i> Pay now', ['/invoice/index'], ['class' => 'btn btn-light btn-sm fw-semibold']) ?>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success-emphasis"><i class="fas fa-check me-1"></i>All paid up</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($attention): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-exclamation-circle"></i>
        <div><strong>Needs your attention:</strong> <?= implode(', ', $attention) ?></div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= Url::to($stat['url']) ?>" class="dash-stat card border-0 shadow-sm h-100 text-decoration-none">
                <div class="card-body d-flex align-items-start gap-3">
                    <span class="dash-stat-icon bg-<?= $stat['color'] ?>-subtle text-<?= $stat['color'] ?>-emphasis">
                        <i class="<?= $stat['icon'] ?>"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="small text-body-secondary"><?= $stat['label'] ?></div>
                        <div class="fs-3 fw-bold text-body lh-sm"><?= $stat['value'] ?></div>
                        <div class="small text-body-secondary"><?= Html::encode($stat['note']) ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 fw-bold mb-0">Upcoming Renewals</h2>
                <?= Html::a('Renewal center', ['/renewal/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
            </div>
            <?php if (!$upcomingServices): ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <div>No services expire within the next 60 days.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-body-secondary">
                            <tr><th class="ps-3">Service</th><th>Expires</th><th>Status</th><th class="text-end pe-3"></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingServices as $service): ?>
                                <?php
                                $days = (int) $today->diff(new \DateTimeImmutable($service['expiry']))->format('%r%a');
                                [$badge, $text] = match (true) {
                                    $days < 0 => ['bg-danger', 'Expired'],
                                    $days === 0 => ['bg-danger', 'Expires today'],
                                    $days <= 15 => [$days <= 5 ? 'bg-danger' : 'bg-warning text-dark', "{$days} day" . ($days === 1 ? '' : 's') . ' left'],
                                    default => ['bg-info-subtle text-info-emphasis', "{$days} days left"],
                                };
                                $key = ['type' => $service['type'], 'id' => $service['id']]; ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold"><?= Html::encode($service['name']) ?></div>
                                        <div class="small text-body-secondary"><?= Html::encode(ucfirst($service['type'])) ?></div>
                                    </td>
                                    <td class="text-nowrap"><?= $formatter->asDate($service['expiry'], 'php:d M Y') ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= $text ?></span></td>
                                    <td class="text-end text-nowrap pe-3">
                                        <?= Html::a('View', ['/service/view'] + $key, ['class' => 'btn btn-sm btn-outline-secondary me-1']) ?>
                                        <?= $this->render('@frontend/views/renewal/_action', $key + ['renewal' => $service['renewal']]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 fw-bold mb-0">Recent Invoices</h2>
                <?= Html::a('All invoices', ['/invoice/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
            </div>
            <?php if (!$recentInvoices): ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="fas fa-file-invoice fa-2x mb-2 opacity-50"></i>
                    <div>No invoices yet.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-body-secondary">
                            <tr><th class="ps-3">Invoice</th><th>Issued</th><th>Due</th><th class="text-end">Amount</th><th class="pe-3">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInvoices as $invoice): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= Html::a(Html::encode($invoice->invoice_number), ['/invoice/view', 'number' => $invoice->invoice_number]) ?></td>
                                    <td class="text-nowrap"><?= $formatter->asDate($invoice->created_at, 'php:d M Y') ?></td>
                                    <td class="text-nowrap"><?= $invoice->due_date ? $formatter->asDate($invoice->due_date, 'php:d M Y') : '-' ?></td>
                                    <td class="text-end text-nowrap"><?= $formatter->asCurrency($invoice->total_payable, 'INR') ?></td>
                                    <td class="pe-3">
                                        <span class="badge <?= $invoiceBadge((string) $invoice->status) ?>"><?= Html::encode(Invoice::getStatuses()[$invoice->status] ?? $invoice->status) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Quick Actions</h2>
            </div>
            <div class="card-body d-grid gap-2">
                <?= Html::a('<i class="fas fa-headset fa-fw me-2"></i>Open a support ticket', ['/support/create'], ['class' => 'btn btn-outline-primary text-start']) ?>
                <?= Html::a('<i class="fas fa-credit-card fa-fw me-2"></i>Payment history', ['/payment/index'], ['class' => 'btn btn-outline-secondary text-start']) ?>
                <?= Html::a('<i class="fas fa-user-cog fa-fw me-2"></i>Edit profile', ['/account/profile'], ['class' => 'btn btn-outline-secondary text-start']) ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h2 class="h6 fw-bold mb-0">Recent Activity</h2>
                <?= Html::a('Timeline', ['/account/activity'], ['class' => 'btn btn-sm btn-link text-decoration-none p-0']) ?>
            </div>
            <?php if (!$activities): ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="fas fa-stream fa-2x mb-2 opacity-50"></i>
                    <div>No recent activity.</div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($activities as $activity): ?>
                        <a href="<?= Html::encode($activity['url']) ?>" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                            <i class="<?= Html::encode($activity['icon']) ?> fa-fw text-primary mt-1"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small"><?= Html::encode($activity['title']) ?></div>
                                <div class="small text-body-secondary text-truncate"><?= Html::encode($activity['description']) ?></div>
                            </div>
                            <div class="small text-body-secondary text-nowrap"><?= $formatter->asRelativeTime($activity['time']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
