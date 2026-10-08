<?php
use yii\bootstrap5\Html;
use common\components\CustomYii;

/** @var common\models\Invoice $model */
/** @var float $subtotal */
/** @var float $cgstAmount */
/** @var float $sgstAmount */
/** @var float $igstAmount */
/** @var float $grandTotal */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice Reference Matrix - <?= Html::encode($model->invoice_number) ?></title>
    
    <!-- Link dependencies -->
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link href="https://googleapis.com" rel="stylesheet">
        <style>
            /**
             * Miracle Web Technologies - Performance Print Stylesheet
             */
            body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                font-size: 14px;
                color: #1e293b;
                background-color: #f1f5f9;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .invoice-wrapper {
                max-width: 850px;
                margin: 40px auto;
                padding: 40px;
                background: #ffffff;
                border-radius: 0.75rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            }

            .brand-header {
                background-color: #0f172a !important;
                border-bottom: 5px solid #00a3e0 !important;
                border-radius: 0.5rem 0.5rem 0 0;
                padding: 35px;
            }

            .table-premium thead tr th {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
                color: #ffffff !important;
                font-weight: 600;
                border: none;
                padding: 14px;
            }

            .table-premium tbody tr td {
                padding: 14px;
                vertical-align: middle;
                border-color: #f1f5f9;
            }

            .banking-panel {
                background-color: #f8fafc;
                border-left: 4px solid #00a3e0;
                border-radius: 0.375rem;
                padding: 24px;
            }

            .thankyou-banner {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
                color: #ffffff !important;
                font-size: 18px;
                font-weight: 700;
                text-align: center;
                padding: 16px;
                border-radius: 0.5rem;
                letter-spacing: 0.5px;
            }
        </style>
 </head>
<body>

<!-- DASHBOARD BUTTON INTERFACE -->
<div class="container text-center my-4 no-print">
    <button onclick="triggerPremiumInvoiceDownload('pdf-invoice-container', '<?= Html::encode($model->invoice_number) ?>')" class="btn btn-dark px-4 py-2 fw-bold shadow-sm">
        📥 Compile and Download Crisp PDF Document Asset
    </button>
</div>

<div class="invoice-wrapper" id="pdf-invoice-container">
    
    <!-- 🏢 BRAND LOGO NAVIGATION HEADER CONTAINER -->
    <header class="brand-header d-flex justify-content-between align-items-center text-white flex-wrap gap-4">
        <div class="d-flex align-items-center gap-3" align="center">
            <img src="https://miraclewebtechnologies.com/images/logo.png" alt="Miracle Logo" style="height: 60px; max-width: 180px; object-fit: contain; display: block;">
            <div>
                <h4 class="fw-bold m-0 text-white" style="color: #cbd5e1;"><?= Html::encode(CustomYii::getSetting('site_name', 'Miracle Web Technologies')) ?></h4>
                <p class="m-0 small" style="color: #00a3e0 !important; font-style: italic;">Upgrade your business with us</p>
            </div>
        </div>
        <div class="text-md-end small lh-lg" style="color: #cbd5e1;">
            <strong class="text-white">Headquarters:</strong> A-1/9, Street No. 4, East Krishna Nagar, Delhi-110051<br>
            <strong>Support:</strong> +<?= Html::encode(CustomYii::getSetting('mobile_1', '919654511842')) ?><br>
            <strong>Email:</strong> <?= Html::encode(CustomYii::getSetting('email_1', 'info@miraclewebtechnologies.com')) ?>
        </div>
    </header>

    <!-- 📄 CLIENT INTAKE DETAILS METADATA CARD -->
    <div class="container-fluid my-5 px-0">
        <div class="row align-items-center">
            <div class="col-6">
                <span class="text-uppercase text-muted fw-bold small tracking-wider">Invoiced To:</span>
                <h4 class="fw-bold text-dark mt-1 mb-2"><?= Html::encode($model->client_name) ?></h4>
                <p class="text-muted m-0"><?= Html::encode($model->client_address_line_1 ?? 'New Delhi, India') ?></p>
            </div>
            <div class="col-6 text-end">
                <div class="d-inline-block bg-light p-3 rounded text-start">
                    <p class="mb-1 small"><strong>Invoice ID Reference:</strong> <span class="text-primary fw-bold"><?= Html::encode($model->invoice_number) ?></span></p>
                    <p class="m-0 small"><strong>Generation Date:</strong> <?= date("d-M-Y", (int) $model->created_at); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 📊 REVENUE TRANSACTION TABLE -->
    <div class="table-responsive my-4">
        <table class="table table-bordered table-premium align-middle">
            <thead>
                <tr>
                    <th style="width: 20%;">Category</th>
                    <th style="width: 45%;">Description Vector Spec</th>
                    <th style="width: 15%;" class="text-center">Cycle Limits</th>
                    <th style="width: 20%;" class="text-end">Total Price</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light text-dark">
                    <td colspan="4" class="small text-secondary py-2" style="font-size: 0.85rem;">
                        💡 Renewal of Domain and Hosting (cretapackaging.com) for 1 year. Target Expiry Boundary: 25/01/2024.
                    </td>
                </tr>
                <?php foreach ($model->invoiceItems as $item): ?>
                    <tr>
                        <td class="fw-bold text-dark"><?= Html::encode($item->category) ?></td>
                        <td class="text-muted small"><?= Html::encode($item->description) ?></td>
                        <td class="text-center small"><?= Html::encode($item->duration_quantity) ?></td>
                        <td class="text-end fw-bold text-dark">INR <?= number_format((float) $item->total_price, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 🗄️ SETTLEMENT BLOCK & TAX SUMMARY GRID -->
    <div class="container-fluid my-5 px-0">
        <div class="row g-4">
            <!-- Left Side: Bank Accounts Instructions -->
            <div class="col-sm-6">
                <div class="banking-panel shadow-sm">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 0.95rem;">🏦 Remittance Banking Instructions</h5>
                    <table class="table table-sm table-borderless m-0 small">
                        <tr><td class="text-muted py-1" style="width: 45%;">Account Holder:</td><td class="fw-bold text-dark py-1">Nidhi Trivedi</td></tr>
                        <tr><td class="text-muted py-1">Account Number:</td><td class="fw-bold text-primary py-1">3139484379</td></tr>
                        <tr><td class="text-muted py-1">Bank Name:</td><td class="fw-semibold text-dark py-1">State Bank of India</td></tr>
                        <tr><td class="text-muted py-1">IFSC Identifier:</td><td class="fw-bold text-dark py-1">CBIN0281278</td></tr>
                    </table>
                </div>
            </div>
            
            <!-- Right Side: Precise Calculation Outputs -->
            <div class="col-sm-6 text-end">
                <div class="ms-auto" style="max-width: 320px;">
                    <table class="table table-sm table-borderless small">
                        <tr><td class="text-muted text-start py-1">Subtotal Base Amount:</td><td class="text-dark fw-medium py-1">INR <?= number_format($subtotal, 2) ?></td></tr>
                        <tr><td class="text-slate-500 text-start py-1">Adjusted Discount:</td><td class="text-danger py-1">-INR <?= number_format((float) $model->discount_amount, 2) ?></td></tr>
                        
                        <?php if ($cgstAmount > 0): ?>
                            <tr><td class="text-muted text-start py-1">CGST (<?= $model->cgst_percent ?>%):</td><td class="text-dark py-1">INR <?= number_format($cgstAmount, 2) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($sgstAmount > 0): ?>
                            <tr><td class="text-muted text-start py-1">SGST (<?= $model->sgst_percent ?>%):</td><td class="text-dark py-1">INR <?= number_format($sgstAmount, 2) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($igstAmount > 0): ?>
                            <tr><td class="text-muted text-start py-1">IGST (<?= $model->igst_percent ?>%):</td><td class="text-dark py-1">INR <?= number_format($igstAmount, 2) ?></td></tr>
                        <?php endif; ?>
                        
                        <tr class="border-top" style="border-color: #cbd5e1 !important;">
                            <td class="text-dark fw-bold text-start py-3" style="font-size: 1.05rem;">Grand Settled Total:</td>
                            <td class="text-primary fw-bold py-3" style="font-size: 1.05rem;">INR <?= number_format($grandTotal, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SUCCESS BANNER FOOTER -->
    <div class="mt-5">
        <div class="thankyou-banner mb-3 shadow-sm">
            Thank You For Your Valued Business!
        </div>
        <p class="text-center text-muted small mt-2 m-0">
            ℹ️ *This is a system-authenticated cPanel transaction manifest record. Explicit physical signature criteria are waived.*
        </p>
    </div>

</div>

        <!-- Engine Scripts Dependency Array -->
        <!-- ⚙️ COMPILATION SCRIPTS STORAGE -->
<script src="https://googleapis.com"></script>
<script src="https://cloudflare.com"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
        <script src="http://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
        <script>
                /**
                 * Miracle Web Technologies - High Fidelity PDF Vector Snapshot Loop
                 */
                /**
                 * Miracle Web Technologies - High Fidelity PDF Vector Canvas Generator
                 * Uses html2pdf.js to snapshot target DOM elements cleanly onto A4 sheets
                 */
                function triggerPremiumInvoiceDownload(containerId, invoiceNumber) {
                    var targetElement = document.getElementById(containerId);

                    if (!targetElement) {
                        console.error("Target invoice canvas container not found.");
                        return;
                    }

                    // Explicit configuration rules to satisfy high-DPI scaling limits
                    var conversionOptions = {
                        margin: 0.2, // Perfect safety padding margins to prevent text clipping
                        filename: 'Invoice_' + invoiceNumber + '.pdf',
                        image: {type: 'jpeg', quality: 1.00}, // Full asset color reproduction
                        html2canvas: {
                            scale: 2, // Double pixel scale ratio for ultra-sharp text printing
                            letterRendering: true,
                            useCORS: true // Unlocks external assets image reading streams
                        },
                        jsPDF: {unit: 'in', format: 'a4', orientation: 'portrait'}
                    };

                    // Execute the rendering loop promise cleanly
                    html2pdf().set(conversionOptions).from(targetElement).save();
                }

        </script>
    </body>
</html>
