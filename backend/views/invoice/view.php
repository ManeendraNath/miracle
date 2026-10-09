<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var float $subtotal */
/** @var float $cgstAmount */
/** @var float $sgstAmount */
/** @var float $igstAmount */
/** @var float $grandTotal */

$this->title = 'Invoice #' . $model->invoice_number;
?>

<div class="container-fluid py-4">
    <!-- Invoice Control Panel Header Card -->
    <div class="card card-outline card-success shadow no-print mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="m-0 font-weight-bold text-dark"><i class="fas fa-file-invoice text-success mr-2"></i> Invoice Ledger Record</h4>
                <small class="text-muted">Review client billing metrics or compile print configurations</small>
            </div>
            <div>
                <a href="<?= Url::to(['index']) ?>" class="btn btn-secondary px-3 mr-2">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Grid
                </a>
                <button onclick="window.print();" class="btn btn-lg btn-primary px-4 shadow-sm">
                    <i class="fas fa-print mr-1"></i> Download PDF / Print
                </button>
            </div>
        </div>
    </div>

    <!-- Main Printable Invoice Canvas Base Area -->
    <div class="invoice p-5 bg-white border rounded shadow-sm position-relative">
        
        <!-- Watermark Status Tag Badge Overlay -->
        <div class="position-absolute" style="top: 40px; right: 50px;">
            <?php 
            $statusClass = 'badge-secondary';
            if ($model->status === 'Paid') $statusClass = 'badge-success';
            if ($model->status === 'Unpaid') $statusClass = 'badge-danger';
            if ($model->status === 'Partially_Paid') $statusClass = 'badge-warning';
            ?>
            <span class="badge <?= $statusClass ?> p-3 text-uppercase font-weight-bold tracking-wider" style="font-size: 1.1rem; letter-spacing: 1px;">
                <?= str_replace('_', ' ', $model->status) ?>
            </span>
        </div>

        <!-- 🏢 Brand Heading & Company Metadata Row -->
        <div class="row align-items-center mb-5">
            <div class="col-md-6">
                <h2 class="text-primary font-weight-bold m-0" style="letter-spacing: -0.5px;">
                    <i class="fas fa-cubes mr-2"></i>MIRACLE WEB TECHNOLOGIES
                </h2>
                <p class="text-muted text-sm mt-1 mb-0">
                    Premium Enterprise Engineering & Systems Architectures<br>
                    Support Line: admin@miraclewebtechnologies.com
                </p>
            </div>
            <div class="col-col-md-6 text-right pt-4">
                <h1 class="text-uppercase font-weight-light text-muted m-0" style="font-size: 2.5rem;">INVOICE</h1>
                <p class="text-dark font-weight-bold m-0">Reference ID: <span class="text-primary"><?= Html::encode($model->invoice_number) ?></span></p>
            </div>
        </div>

        <hr class="my-4">

        <!-- 👥 Billing Addresses Columns -->
        <div class="row mb-5">
            <div class="col-6">
                <p class="text-uppercase font-weight-bold text-muted text-xs mb-1">Invoiced To:</p>
                <h5 class="font-weight-bold text-dark mb-1"><?= Html::encode($model->client_name) ?></h5>
                <p class="text-secondary text-sm mb-0">
                    <?= Html::encode($model->client_address_line_1) ?><br>
                    <?= Html::encode($model->client_address_line_2) ?>
                </p>
            </div>
            <div class="col-6 text-right">
                <p class="text-uppercase font-weight-bold text-muted text-xs mb-1">Timeline Dates:</p>
                <p class="text-sm text-dark mb-1"><strong>Issue Date:</strong> <?= date('d M Y', strtotime($model->created_at ?? date('Y-m-d'))) ?></p>
                <p class="text-sm text-danger mb-0"><strong>Due Date:</strong> <?= !empty($model->due_date) ? date('d M Y', strtotime($model->due_date)) : 'Upon Receipt' ?></p>
            </div>
        </div>

        <!-- 📊 Transaction Ledger Table Sheet -->
        <table class="table table-striped table-borderless my-4">
            <thead>
                <tr class="bg-primary text-white text-uppercase text-sm">
                    <th style="width: 70%">Line Item Details</th>
                    <th class="text-right" style="width: 30%">Total Taxable Base Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="align-middle py-3">
                        <h6 class="font-weight-bold text-dark mb-0">Professional Web Architecture Services</h6>
                        <small class="text-muted">Custom development sprint cycles, feature modules, and cloud deployment optimizations</small>
                    </td>
                    <td class="text-right align-middle py-3 font-weight-bold text-dark" style="font-size: 1.1rem;">
                        ₹<?= number_format($subtotal, 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 🧮 Summary Computation Blocks Row -->
        <div class="row justify-content-end mt-5">
            <div class="col-md-5">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless text-sm text-dark">
                        <tr>
                            <td class="text-muted py-1">Subtotal Gross:</td>
                            <td class="text-right py-1 font-weight-bold">₹<?= number_format($subtotal, 2) ?></td>
                        </tr>
                        <?php if ((float)$model->discount_amount > 0): ?>
                        <tr class="text-danger">
                            <td class="py-1">Discount Applied (<?= Html::encode($model->coupon_code) ?>):</td>
                            <td class="text-right py-1 font-weight-bold">-₹<?= number_format($model->discount_amount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <!-- Indian GST Breaks -->
                        <?php if ($cgstAmount > 0): ?>
                        <tr>
                            <td class="text-muted py-1">CGST (<?= (float)$model->cgst_percent ?>%):</td>
                            <td class="text-right py-1">₹<?= number_format($cgstAmount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($sgstAmount > 0): ?>
                        <tr>
                            <td class="text-muted py-1">SGST (<?= (float)$model->sgst_percent ?>%):</td>
                            <td class="text-right py-1">₹<?= number_format($sgstAmount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($igstAmount > 0): ?>
                        <tr>
                            <td class="text-muted py-1">IGST (<?= (float)$model->igst_percent ?>%):</td>
                            <td class="text-right py-1">₹<?= number_format($igstAmount, 2) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr class="border-top" style="font-size: 1.3rem;">
                            <td class="font-weight-bold text-primary py-2">Grand Total:</td>
                            <td class="text-right font-weight-bold text-success py-2">₹<?= number_format($grandTotal, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <hr class="my-5">
        <div class="text-center text-muted text-xs">
            <p class="m-0">Thank you for your business with Miracle Web Technologies.</p>
            <p class="m-0 text-xxs mt-1">This is an automated system generated billing accounting summary document. No physical signature parameters are required.</p>
        </div>
    </div>
</div>

<!-- NATIVE CSS MEDIA PRINT OVERRIDES OVERLAY STYLE RULES -->
<style>
@media print {
    .no-print, .main-footer, .brand-link, .main-header, .sidebar, .nav { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: #fff !important; }
    .invoice { border: none !important; padding: 0 !important; width: 100% !important; margin: 0 !important; box-shadow: none !important; }
    body { background-color: #ffffff !important; font-size: 12px !important; }
    .badge { border: 1px solid #999 !important; color: #000 !important; background: transparent !important; }
}
</style>
