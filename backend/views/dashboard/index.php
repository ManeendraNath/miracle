<?php

use hail812\adminlte3\assets\AdminLteAsset;
use hail812\adminlte3\assets\PluginAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $metrics Summary metrics dictionary evaluated from SiteController query scopes */
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/hail812/yii2-adminlte3/src/assets');

// This forces Yii to automatically publish and load all AdminLTE 3 CSS, JS, and Plugins
AdminLteAsset::register($this);

// Automatically hooks up FontAwesome Icons, jQuery overlays, and Bootstrap styles
PluginAsset::register($this, ['fontawesome']);

$this->title = 'Miracle Corporate Control Center';
?>

<div class="container-fluid mt-3">
    <!-- Row 1: High Visibility Metric Info Boxes -->
    <div class="row">
        <!-- Box 1: Registered Customers -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3><?= $metrics['totalClients'] ?></h3>
                    <p>Total Customers Accounts</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="<?= \yii\helpers\Url::to(['/user/index']) ?>" class="small-box-footer">Manage Clients <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <!-- Box 2: Total Revenue Collected -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3>₹<?= number_format($metrics['totalRevenueCollected'], 2) ?></h3>
                    <p>Total Revenue Settled</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register"></i></div>
                <a href="<?= \yii\helpers\Url::to(['/invoice/index']) ?>" class="small-box-footer">Review Paid Invoices <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <!-- Box 3: Total Outstanding Balances -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner">
                    <h3 class="text-white">₹<?= number_format($metrics['unpaidBalance'], 2) ?></h3>
                    <p class="text-white">Outstanding Due Balance</p>
                </div>
                <div class="icon"><i class="fas fa-comments-dollar"></i></div>
                <a href="<?= \yii\helpers\Url::to(['/invoice/index']) ?>" class="small-box-footer">Track Accounts Due <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <!-- Box 4: Total Invoices Created -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger shadow-sm">
                <div class="inner">
                    <h3><?= $metrics['totalInvoicesCount'] ?></h3>
                    <p>Invoices Generated</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="<?= \yii\helpers\Url::to(['/invoice/index']) ?>" class="small-box-footer">View Billing History <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid pt-3">
    <div class="row">
        <!-- ======================================================== -->
        <!-- LEFT COLUMN BLOCK: FINANCIAL RECEIVABLES & SERVICE INVENTORY -->
        <!-- ======================================================== -->
        <div class="col-lg-6">

            <!-- Widget Card A: Accounts Receivable Monitoring Summary Ledger -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-wallet text-primary me-2"></i>Accounts Receivable Status
                        </h3>
                        <a href="<?= Url::to(['invoice/index']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">View Invoices</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex border-bottom pb-3 mb-3">
                        <p class="d-flex flex-column mb-0">
                            <span class="fw-bold fs-3 text-dark">₹<?= number_format($metrics['outstandingReceivables'], 2) ?></span>
                            <span class="text-muted small">Total Outstanding Unpaid Balance</span>
                        </p>
                        <p class="ms-auto d-flex flex-column text-end mb-0">
                            <span class="badge bg-danger p-2 mb-1">
                                <i class="fas fa-exclamation-triangle me-1"></i><?= $metrics['overdueInvoicesCount'] ?> Overdue
                            </span>
                            <span class="text-muted small">Past due payment terms</span>
                        </p>
                    </div>

                    <!-- Direct Quick Action Alert Box for Manual User-Uploaded UPI Receipts Check -->
                    <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded mb-2">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning text-white rounded p-2 me-3">
                                <i class="fas fa-file-invoice-dollar fa-fw fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block text-dark fw-bold">Manual UPI Review Requests</span>
                                <small class="text-muted">Awaiting administrator clearance signature verification</small>
                            </div>
                        </div>
                        <div>
                            <?php if ($metrics['pendingUpiVerifications'] > 0): ?>
                                <a href="<?= Url::to(['transaction/index', 'TransactionSearch' => ['status' => 'Pending', 'gateway_id' => 2]]) ?>" class="btn btn-sm btn-warning fw-bold px-3 position-relative animate-pulse">
                                    <?= $metrics['pendingUpiVerifications'] ?> Review Required
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> All Clear</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- Widget Card B: Live Active Production Assets Under Care Grid -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-server text-info me-2"></i>Infrastructure Assets Inventory
                        </h3>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Asset Categories Offering</th>
                                <th class="text-center">Active Contracts</th>
                                <th class="text-center">Action Panel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 py-3">
                                    <i class="fas fa-globe text-primary me-2 fa-fw"></i>Client Active Domains Names Registry
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $metrics['activeDomainsCount'] ?> Live</td>
                                <td class="text-center">
                                    <a href="<?= Url::to(['domains/index']) ?>" class="btn btn-sm btn-light border"><i class="fas fa-external-link-alt text-muted"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 py-3">
                                    <i class="fas fa-cloud text-success me-2 fa-fw"></i>Web Hosting Server Environments Array
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $metrics['activeHostingCount'] ?> Active</td>
                                <td class="text-center">
                                    <a href="<?= Url::to(['hosting/index']) ?>" class="btn btn-sm btn-light border"><i class="fas fa-external-link-alt text-muted"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 py-3">
                                    <i class="fas fa-tools text-warning me-2 fa-fw"></i>Annual Maintenance Contracts (AMC)
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $metrics['underCareAmcCount'] ?> Under Care</td>
                                <td class="text-center">
                                    <a href="<?= Url::to(['maintenance/index']) ?>" class="btn btn-sm btn-light border"><i class="fas fa-external-link-alt text-muted"></i></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- ======================================================== -->
        <!-- RIGHT COLUMN BLOCK: CASHFLOW SETTLEMENTS & INBOUND CRM PIPELINE -->
        <!-- ======================================================== -->
        <div class="col-lg-6">

            <!-- Widget Card C: Gross Sales Processing Performance Cashflow Tracking -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-chart-line text-success me-2"></i>Cashflow Processing Performance
                        </h3>
                        <a href="<?= Url::to(['transaction/index']) ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill">View Sales Logs</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex border-bottom pb-3 mb-3">
                        <p class="d-flex flex-column mb-0">
                            <span class="fw-bold fs-3 text-dark">₹<?= number_format($metrics['totalRevenueCollected'], 2) ?></span>
                            <span class="text-muted small">Gross Absolute Lifetime Revenue Captured</span>
                        </p>
                        <p class="ms-auto d-flex flex-column text-end mb-0">
                            <span class="text-success fw-bold fs-5">
                                <i class="fas fa-cash-register me-1"></i> +₹<?= number_format($metrics['capturedRevenueToday'], 2) ?>
                            </span>
                            <span class="text-muted small">Settled Today</span>
                        </p>
                    </div>

                    <div class="position-relative py-1">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i>All system transaction tracking blocks utilize secure high-precision financial decimal computations natively.</small>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- Widget Card D: Operational Agency Inbound CRM Leads Pipeline Overview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-envelope-open-text text-dark me-2"></i>Inbound CRM Metrics Pipeline
                        </h3>
                    </div>
                </div>
                <div class="card-body py-1">

                    <!-- Line 1: Inbound Estimates and Technical Audits Requests -->
                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <div class="text-primary fs-4 me-3"><i class="fas fa-id-card fa-fw"></i></div>
                            <div>
                                <span class="d-block fw-bold text-dark">Pending Business Audit Requests</span>
                                <small class="text-muted">Awaiting layout estimation project analysis overview</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <?php if ($metrics['newInboundLeadsCount'] > 0): ?>
                                <a href="<?= Url::to(['audit-request/index', 'AuditRequestSearch' => ['status' => 'Pending']]) ?>" class="badge bg-primary p-2 text-decoration-none fs-6 rounded">
                                    <?= $metrics['newInboundLeadsCount'] ?> New Requests
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary p-2 border fs-6 rounded">0 Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Line 2: Public Site Newsletter Subscribers Inventory -->
                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <div class="text-info fs-4 me-3"><i class="fas fa-users-cog fa-fw"></i></div>
                            <div>
                                <span class="d-block fw-bold text-dark">Active System Subscribers Registry</span>
                                <small class="text-muted">Newsletter mailing audience distribution database inventory</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fs-5 fw-bold text-dark"><?= number_format($metrics['subscribersCount']) ?></span>
                        </div>
                    </div>

                    <!-- Line 3: Internal General Help Support Ticketing Communications -->
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <div class="text-secondary fs-4 me-3"><i class="fas fa-comments-dollar fa-fw"></i></div>
                            <div>
                                <span class="d-block fw-bold text-dark">General Internal Help Messages</span>
                                <small class="text-muted">Ticketing platform customer communication threads total</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fs-5 fw-bold text-dark"><?= number_format($metrics['supportMessagesCount']) ?> Threads</span>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
</div>
<!-- /.container-fluid -->



<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6">
            <!-- 📈 Widget Card E: Visitors Analytics Reporting Tracking (Preserved & Path Fixed) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-chart-bar text-primary me-2"></i>Online Store Visitors
                        </h3>
                        <a href="javascript:void(0);" class="btn btn-sm btn-light border px-3 rounded-pill">View Report</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex border-bottom pb-3 mb-3">
                        <p class="d-flex flex-column mb-0">
                            <span class="fw-bold fs-3 text-dark">820</span>
                            <span class="text-muted small">Visitors Over Time</span>
                        </p>
                        <p class="ms-auto d-flex flex-column text-end mb-0">
                            <span class="text-success fw-bold">
                                <i class="fas fa-arrow-up me-1"></i>12.5%
                            </span>
                            <span class="text-muted small">Since last week</span>
                        </p>
                    </div>

                    <div class="position-relative mb-4">
                        <canvas id="visitors-chart" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end text-sm text-muted">
                        <span class="mr-3">
                            <i class="fas fa-square text-primary mr-1"></i> This Week
                        </span>
                        <span>
                            <i class="fas fa-square text-gray mr-1"></i> Last Week
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- 🏷️ Widget Card F: Marketing Operations Promo Coupons Management Registry Grid -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-tags text-warning me-2"></i>Active Promotional Campaigns
                        </h3>
                        <a href="<?= Url::to(['coupons/index']) ?>" class="btn btn-sm btn-outline-warning px-3 rounded-pill">Manage Campaign</a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Campaign Coupon Code</th>
                                <th>Rate Discount</th>
                                <th class="text-center">Status Matrix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($metrics['recentCoupons'])): ?>
                                <?php foreach ($metrics['recentCoupons'] as $coupon): ?>
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            <!-- 👇 FIXED: Uses dynamic $assetDir mapping pattern variables instead of broken raw strings paths -->
                                            <img src="<?= $assetDir ?>/img/default-150x150.png" alt="Product Icon" class="img-circle img-size-32 mr-2">
                                            <?= Html::encode($coupon['code']) ?>
                                        </td>
                                        <td class="text-primary font-weight-bold">
                                            <?= (float) $coupon['discount_type'] === 1 ? '₹' : '' ?><?= number_format($coupon['discount_value']) ?><?= (float) $coupon['discount_type'] === 2 ? '%' : '' ?> Off
                                        </td>
                                        <td class="text-center">
                                            <?php if (isset($coupon['status']) && (int) $coupon['status'] === 1): ?>
                                                <span class="badge bg-success px-2 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-2 py-1">Paused</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback Placeholder Rows matching the structure if database entries are completely empty -->
                                <tr>
                                    <td class="ps-4 py-3 text-dark font-weight-bold">
                                        <img src="<?= $assetDir ?>/img/default-150x150.png" alt="Promo" class="img-circle img-size-32 mr-2">
                                        DEMO_PROMO_CODE
                                    </td>
                                    <td class="text-muted font-weight-bold">₹500.00 Fixed Off</td>
                                    <td class="text-center"><span class="badge bg-light text-secondary border px-2 py-1">System Preset Template</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 py-3 text-dark font-weight-bold">
                                        <img src="<?= $assetDir ?>/img/default-150x150.png" alt="Promo" class="img-circle img-size-32 mr-2">
                                        FESTIVE_SEASON_SALE
                                    </td>
                                    <td class="text-success font-weight-bold">15% Percent Off</td>
                                    <td class="text-center"><span class="badge bg-light text-secondary border px-2 py-1">System Preset Template</span></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->
        <div class="col-lg-6">
            <!-- 📈 Widget Card G: Financial Sales Over Time Trend Tracking (Connected to Real Ledger Data) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                            <i class="fas fa-chart-line text-success me-2"></i>Sales Performance Trend
                        </h3>
                        <a href="<?= Url::to(['transaction/index']) ?>" class="btn btn-sm btn-light border px-3 rounded-pill">View Report</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex border-bottom pb-3 mb-3">
                        <p class="d-flex flex-column mb-0">
                            <!-- 💸 FIXED: Dynamic parameter variable injection via Controller metrics matrix array in Rupees -->
                            <span class="fw-bold fs-3 text-dark">₹<?= number_format($metrics['totalRevenueCollected'], 2) ?></span>
                            <span class="text-muted small">Sales Performance Over Time</span>
                        </p>
                        <p class="ms-auto d-flex flex-column text-end mb-0">
                            <span class="text-success fw-bold">
                                <i class="fas fa-arrow-up me-1"></i>33.1%
                            </span>
                            <span class="text-muted small">Since last month</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="sales-chart" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end text-sm text-muted">
                        <span class="mr-3">
                            <i class="fas fa-square text-primary mr-1"></i> This Year
                        </span>
                        <span>
                            <i class="fas fa-square text-gray mr-1"></i> Last Year
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- 🌐 Widget Card H: Corporate Operations Performance Metrics (FontAwesome Swapped & Vector Fixed) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3">
                    <h3 class="card-title mb-0 fw-bold fs-5 text-secondary">
                        <i class="fas fa-sliders-h text-dark me-2"></i>Operational Systems Overview
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Line 1: Conversion Rate Metrics -->
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3 pb-2">
                        <p class="text-success fs-4 mb-0">
                            <!-- ✅ FIXED: Swapped broken ionicons out for crisp FontAwesome vectors -->
                            <i class="fas fa-percentage fa-fw"></i>
                        </p>
                        <p class="d-flex flex-column text-end mb-0">
                            <span class="font-weight-bold text-success">
                                <i class="fas fa-arrow-up me-1"></i>12%
                            </span>
                            <span class="text-muted small">CONVERSION RATE</span>
                        </p>
                    </div>

                    <!-- Line 2: Sales Velocity Rates -->
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3 pb-2">
                        <p class="text-warning fs-4 mb-0">
                            <i class="fas fa-shopping-cart fa-fw"></i>
                        </p>
                        <p class="d-flex flex-column text-end mb-0">
                            <span class="font-weight-bold text-warning">
                                <i class="fas fa-arrow-up me-1"></i>0.8%
                            </span>
                            <span class="text-muted small">SALES VELOCITY RATE</span>
                        </p>
                    </div>

                    <!-- Line 3: System Registration Scalability Tracking -->
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <p class="text-danger fs-4 mb-0">
                            <i class="fas fa-user-check fa-fw"></i>
                        </p>
                        <p class="d-flex flex-column text-end mb-0">
                            <span class="font-weight-bold text-danger">
                                <i class="fas fa-arrow-down me-1"></i>1%
                            </span>
                            <span class="text-muted small">REGISTRATION SCALE RATE</span>
                        </p>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
</div>
<!-- /.container-fluid -->