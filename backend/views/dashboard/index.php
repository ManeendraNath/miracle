<?php

use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $metrics Summary metrics dictionary evaluated from SiteController query scopes */

$this->title = 'Dashboard Overview Panel';
?>

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
                                <td class="text-center fw-bold text-dark"><?= \common\models\Domains::find()->where(['status' => 1])->count() ?> Live</td>
                                <td class="text-center">
                                    <a href="<?= Url::to(['domains/index']) ?>" class="btn btn-sm btn-light border"><i class="fas fa-external-link-alt text-muted"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 py-3">
                                    <i class="fas fa-cloud text-success me-2 fa-fw"></i>Web Hosting Server Environments Array
                                </td>
                                <td class="text-center fw-bold text-dark"><?= \common\models\Hosting::find()->where(['status' => 1])->count() ?> Active</td>
                                <td class="text-center">
                                    <a href="<?= Url::to(['hosting/index']) ?>" class="btn btn-sm btn-light border"><i class="fas fa-external-link-alt text-muted"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 py-3">
                                    <i class="fas fa-tools text-warning me-2 fa-fw"></i>Annual Maintenance Contracts (AMC)
                                </td>
                                <td class="text-center fw-bold text-dark"><?= \common\models\Maintenance::find()->where(['status' => 1])->count() ?> Under Care</td>
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
                            <span class="fs-5 fw-bold text-dark"><?= number_format(\common\models\Subscribers::find()->count()) ?></span>
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
                            <span class="fs-5 fw-bold text-dark"><?= number_format(\common\models\Messages::find()->count()) ?> Threads</span>
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
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Online Store Visitors</h3>
                        <a href="javascript:void(0);">View Report</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg">820</span>
                            <span>Visitors Over Time</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> 12.5%
                            </span>
                            <span class="text-muted">Since last week</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="visitors-chart" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> This Week
                        </span>

                        <span>
                            <i class="fas fa-square text-gray"></i> Last Week
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Products</h3>
                    <div class="card-tools">
                        <a href="#" class="btn btn-tool btn-sm">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="#" class="btn btn-tool btn-sm">
                            <i class="fas fa-bars"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Sales</th>
                                <th>More</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <img src="dist/img/default-150x150.png" alt="Product 1" class="img-circle img-size-32 mr-2">
                                    Some Product
                                </td>
                                <td>$13 USD</td>
                                <td>
                                    <small class="text-success mr-1">
                                        <i class="fas fa-arrow-up"></i>
                                        12%
                                    </small>
                                    12,000 Sold
                                </td>
                                <td>
                                    <a href="#" class="text-muted">
                                        <i class="fas fa-search"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="dist/img/default-150x150.png" alt="Product 1" class="img-circle img-size-32 mr-2">
                                    Another Product
                                </td>
                                <td>$29 USD</td>
                                <td>
                                    <small class="text-warning mr-1">
                                        <i class="fas fa-arrow-down"></i>
                                        0.5%
                                    </small>
                                    123,234 Sold
                                </td>
                                <td>
                                    <a href="#" class="text-muted">
                                        <i class="fas fa-search"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="dist/img/default-150x150.png" alt="Product 1" class="img-circle img-size-32 mr-2">
                                    Amazing Product
                                </td>
                                <td>$1,230 USD</td>
                                <td>
                                    <small class="text-danger mr-1">
                                        <i class="fas fa-arrow-down"></i>
                                        3%
                                    </small>
                                    198 Sold
                                </td>
                                <td>
                                    <a href="#" class="text-muted">
                                        <i class="fas fa-search"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="dist/img/default-150x150.png" alt="Product 1" class="img-circle img-size-32 mr-2">
                                    Perfect Item
                                    <span class="badge bg-danger">NEW</span>
                                </td>
                                <td>$199 USD</td>
                                <td>
                                    <small class="text-success mr-1">
                                        <i class="fas fa-arrow-up"></i>
                                        63%
                                    </small>
                                    87 Sold
                                </td>
                                <td>
                                    <a href="#" class="text-muted">
                                        <i class="fas fa-search"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col-md-6 -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Sales</h3>
                        <a href="javascript:void(0);">View Report</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <p class="d-flex flex-column">
                            <span class="text-bold text-lg">$18,230.00</span>
                            <span>Sales Over Time</span>
                        </p>
                        <p class="ml-auto d-flex flex-column text-right">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> 33.1%
                            </span>
                            <span class="text-muted">Since last month</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->

                    <div class="position-relative mb-4">
                        <canvas id="sales-chart" height="200"></canvas>
                    </div>

                    <div class="d-flex flex-row justify-content-end">
                        <span class="mr-2">
                            <i class="fas fa-square text-primary"></i> This year
                        </span>

                        <span>
                            <i class="fas fa-square text-gray"></i> Last year
                        </span>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Online Store Overview</h3>
                    <div class="card-tools">
                        <a href="#" class="btn btn-sm btn-tool">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-tool">
                            <i class="fas fa-bars"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                        <p class="text-success text-xl">
                            <i class="ion ion-ios-refresh-empty"></i>
                        </p>
                        <p class="d-flex flex-column text-right">
                            <span class="font-weight-bold">
                                <i class="ion ion-android-arrow-up text-success"></i> 12%
                            </span>
                            <span class="text-muted">CONVERSION RATE</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                        <p class="text-warning text-xl">
                            <i class="ion ion-ios-cart-outline"></i>
                        </p>
                        <p class="d-flex flex-column text-right">
                            <span class="font-weight-bold">
                                <i class="ion ion-android-arrow-up text-warning"></i> 0.8%
                            </span>
                            <span class="text-muted">SALES RATE</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <p class="text-danger text-xl">
                            <i class="ion ion-ios-people-outline"></i>
                        </p>
                        <p class="d-flex flex-column text-right">
                            <span class="font-weight-bold">
                                <i class="ion ion-android-arrow-down text-danger"></i> 1%
                            </span>
                            <span class="text-muted">REGISTRATION RATE</span>
                        </p>
                    </div>
                    <!-- /.d-flex -->
                </div>
            </div>
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
</div>
<!-- /.container-fluid -->