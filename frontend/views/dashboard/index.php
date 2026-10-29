<?php
declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;

$this->title = 'Customer Portal';
$today = new \DateTimeImmutable('today');

$attentionCount = $openInvoices + $pendingPayments + $openSupportTickets;
?>
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#0b1f4d,#1565c0); color:#fff; min-height:180px;">
    <div class="card-body d-flex align-items-center">
        <div class="flex-grow-1">
            <div class="small text-white-50 mb-2">CUSTOMER PORTAL</div>
            <h1 class="fw-bold mb-2">Welcome back, <?= Html::encode(Yii::$app->user->identity->username) ?></h1>
            <div class="small text-uppercase text-white-50">Customer Portal</div>
            <p class="mb-0 opacity-75">View services, invoices, support requests and account activity from a single dashboard.</p>
        </div>
        <div class="text-end">
            <div class="display-5 fw-bold">
                <?= Yii::$app->formatter->asCurrency($outstandingTotal, 'INR') ?>
            </div>
            <div class="small text-white-50">
                Outstanding Balance
            </div>
        </div>
    </div>
</div>
<?php if ($attentionCount > 0): ?>
    <div class="alert alert-warning shadow-sm">
        <strong>Attention Required</strong>
        <br>
        <?= $openInvoices ?> open invoice(s),
        <?= $pendingPayments ?> payment(s) under review,
        <?= $openSupportTickets ?> support request(s) awaiting action.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">

    <div class="col-md-3">
        <a href="<?= \yii\helpers\Url::to(['/renewal/index']) ?>"
           class="card bg-info text-white text-decoration-none border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-sync-alt fa-2x mb-2"></i>
                </div>
                <h5>Renewals</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="<?= \yii\helpers\Url::to(['/invoice/index']) ?>"
           class="card bg-primary text-white text-decoration-none border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-file-invoice fa-3x mb-3"></i>
                </div>
                <h5>Invoices</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="<?= \yii\helpers\Url::to(['/service/index']) ?>"
           class="card bg-success text-white text-decoration-none border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-cubes fa-2x mb-2"></i>
                </div>
                <h5>Services</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="<?= \yii\helpers\Url::to(['/support/index']) ?>"
           class="card bg-warning text-white text-decoration-none border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-headset fa-2x mb-2"></i>
                </div>
                <h5>Support</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="<?= \yii\helpers\Url::to(['/account/profile']) ?>"
           class="card bg-dark text-white text-decoration-none border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-user-cog fa-2x mb-2"></i>
                </div>
                <h5>Profile</h5>
            </div>
        </a>
    </div>

</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <?php if (!$upcomingServices): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <h5>No upcoming renewals</h5>
                    <div>No services expire within the next 60 days.</div>
                </div>
            <?php else: ?>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Upcoming Renewals</strong>
                    <?= Html::a('Open Renewal Center', ['/renewal/index'], ['class' => 'btn btn-sm btn-primary']) ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Type</th>
                                <th>Expires</th>
                                <th>Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingServices as $service): ?>
                                <?php
                                $expiry = new \DateTimeImmutable($service['expiry']);
                                $days = (int) $today->diff($expiry)->format('%r%a');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= Html::encode($service['name']) ?></td>
                                    <td><?= Html::encode(ucfirst($service['type'])) ?></td>
                                    <td><?= Yii::$app->formatter->asDate($service['expiry'], 'php:d M Y') ?></td>
                                    <td>
                                        <?php if ($days < 0): ?>
                                            <span class="badge bg-danger">Expired</span>
                                        <?php elseif ($days === 0): ?>
                                            <span class="badge bg-danger">Expires Today</span>
                                        <?php elseif ($days <= 5): ?>
                                            <span class="badge bg-danger">
                                                <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
                                            </span>
                                        <?php elseif ($days <= 15): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">

                                        <?=
                                        Html::a(
                                                'View',
                                                [
                                                    '/service/view',
                                                    'type' => $service['type'],
                                                    'id' => $service['id'],
                                                ],
                                                [
                                                    'class' => 'btn btn-sm btn-outline-secondary me-1',
                                                ]
                                        )
                                        ?>

                                        <?=
                                        Html::a(
                                                'Renew',
                                                [
                                                    '/checkout/renew',
                                                    'type' => $service['type'],
                                                    'id' => $service['id'],
                                                ],
                                                [
                                                    'class' =>
                                                    'btn btn-sm btn-primary',
                                                ]
                                        )
                                        ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Billing Summary</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted">Total Invoices</div>
                        <div class="fs-3 fw-bold">
                            <?= $totalInvoices ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted">Open Invoices</div>
                        <div class="fs-3 fw-bold text-danger">
                            <?= $openInvoices ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted">Outstanding</div>
                        <div class="fs-4 fw-bold">
                            <?= Yii::$app->formatter->asCurrency($outstandingTotal, 'INR') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted">Payments Under Review</div>
                        <div class="fs-3 fw-bold text-warning">
                            <?= $pendingPayments ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <div class="col-12">

        <div class="card shadow-sm">

            <div class="card-header">

                <strong>
                    Recent Activity
                </strong>

            </div>

            <div class="card-body p-0">

                <?php if (empty($activities)): ?>

                    <div class="text-center py-4 text-muted">

                        No recent activity available.

                    </div>

                <?php else: ?>

                    <div class="list-group list-group-flush">

                        <?php foreach ($activities as $activity): ?>

                            <div
                                class="
                                list-group-item
                                "
                                >

                                <div
                                    class="
                                    d-flex
                                    justify-content-between
                                    "
                                    >

                                    <div>

                                        <div
                                            class="
                                            fw-semibold
                                            "
                                            >

                                            <?=
                                            Html::encode(
                                                    $activity['title']
                                            )
                                            ?>

                                        </div>

                                        <div
                                            class="
                                            small
                                            text-muted
                                            "
                                            >

                                            <?=
                                            Html::encode(
                                                    $activity['message']
                                            )
                                            ?>

                                        </div>

                                    </div>

                                    <div
                                        class="
                                        text-muted
                                        small
                                        text-nowrap
                                        "
                                        >

                                        <?=
                                                Yii::$app
                                                ->formatter
                                                ->asRelativeTime(
                                                        $activity['time']
                                                )
                                        ?>

                                    </div>

                                </div>

                            </div>

    <?php endforeach; ?>

                    </div>

<?php endif; ?>

            </div>

        </div>

    </div>

</div>

<div class="row g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recent Invoices</strong>
<?= Html::a('View All Invoices', ['/invoice/index'], ['class' => 'btn btn-sm btn-primary']) ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Issued</th>
                                <th>Due</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                                    <?php foreach ($recentInvoices as $invoice): ?>
                                <tr>
                                    <td><?= Html::a(Html::encode($invoice->invoice_number), ['/invoice/view', 'number' => $invoice->invoice_number]) ?></td>
                                    <td><?= Yii::$app->formatter->asDate($invoice->created_at, 'php:d M Y') ?></td>
                                    <td><?= $invoice->due_date ? Yii::$app->formatter->asDate($invoice->due_date, 'php:d M Y') : '-' ?></td>
                                    <td class="text-end"><?= Yii::$app->formatter->asCurrency($invoice->total_payable, 'INR') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match ($invoice->status) {
                                            Invoice::STATUS_PAID => 'bg-success',
                                            Invoice::STATUS_UNPAID => 'bg-danger',
                                            Invoice::STATUS_PARTIALLY_PAID => 'bg-warning text-dark',
                                            Invoice::STATUS_CANCELLED => 'bg-secondary',
                                            default => 'bg-secondary',
                                        };

                                        echo Html::tag(
                                                'span',
                                                Invoice::getStatuses()[$invoice->status] ?? $invoice->status,
                                                ['class' => 'badge ' . $statusClass]
                                        );
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

<?php if (!$recentInvoices): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No invoices available.</td>
                                </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Account Overview</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Domains</div>
                                <h3 class="mb-0"><?= $domainCount ?></h3>
                            </div>
                            <span class="rounded-circle p-3 bg-primary text-white">
                                <i class="fas fa-globe"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Hostings</div>
                                <h3><?= $hostingCount ?></h3>
                            </div>
                            <span class="rounded-circle p-3 bg-primary text-white">
                                <i class="fas fa-server"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Maintenance</div>
                                <h3><?= $maintenanceCount ?></h3>
                            </div>
                            <span class="rounded-circle p-3 bg-primary text-white">
                                <i class="fas fa-tools"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Invoices Due</div>
                                <h3><?= $openInvoices ?></h3>
                            </div>
                            <span class="rounded-circle p-3 bg-primary text-white">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">
                                    Notifications
                                </div>
                                <h3><?= $unreadNotifications ?></h3>
                            </div>
                            <i class="fas fa-bell fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">Open Support Tickets</div>
                                <h3><?= $openSupportTickets ?></h3>
                            </div>
                            <i class="fas fa-headset fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>