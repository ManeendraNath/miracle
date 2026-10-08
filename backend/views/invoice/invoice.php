<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoice Framework | Miracle Web Technologies</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Modern Framework & Typography Links -->
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 🎨 Link your custom CSS matrix -->
    <link href="<?= Yii::$app->request->baseUrl ?>/css/invoice-core.css" rel="stylesheet">
</head>
<body>

<div class="invoice-wrapper" id="pdf-invoice-container">
    
    <!-- 🏢 BRAND LOGO NAVIGATION NAV HEADER -->
    <header class="brand-header d-flex justify-content-between align-items-center text-white flex-wrap gap-4">
        <div class="d-flex align-items-center gap-3">
            <!-- Automatically references your dynamic brand icon path asset natively -->
            <img src="<?= Yii::$app->request->baseUrl ?>/favicon.ico" alt="Logo" style="height: 65px; width: 65px; object-fit: contain;">
            <div>
                <h4 class="fw-bold m-0 text-white">Miracle Web Technologies</h4>
                <p class="m-0 small opacity-75" style="color: #00a3e0 !important; font-style: italic;">Upgrade your business with us</p>
            </div>
        </div>
        <div class="text-md-end small lh-lg" style="color: #cbd5e1;">
            <strong class="text-white">Headquarters:</strong> A-1/9, Street No. 4, East Krishna Nagar, Delhi-110051<br>
            <strong>Support:</strong> +91-9654511842 | 
            <strong>Email:</strong> info@miraclewebtechnologies.com<br>
            <strong>Portal:</strong> ://miraclewebtechnologies.com
        </div>
    </header>

    <!-- 📄 INVOICE METADATA ROW -->
    <div class="container-fluid my-5 px-0">
        <div class="row align-items-center">
            <div class="col-6">
                <span class="text-uppercase text-muted fw-bold small tracking-wider">Invoiced To:</span>
                <h4 class="fw-bold text-dark mt-1 mb-2">Creta Packaging</h4>
                <p class="text-muted m-0">New Delhi, India</p>
            </div>
            <div class="col-6 text-end">
                <div class="d-inline-block bg-light p-3 rounded text-start">
                    <p class="mb-1 small"><strong>Invoice Number:</strong> <span class="text-primary fw-bold">MWT1011</span></p>
                    <p class="m-0 small"><strong>Issue Date:</strong> <?= date("d-M-Y"); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 📊 LINE ITEMS CORE MATRIX -->
    <div class="table-responsive my-4">
        <table class="table table-bordered table-premium align-middle">
            <thead>
                <tr>
                    <th style="width: 20%;">Category</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;" class="text-center">Duration</th>
                    <th style="width: 12%;" class="text-end">Unit Price</th>
                    <th style="width: 13%;" class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light text-dark fw-semibold">
                    <td colspan="5" class="small text-secondary py-2">
                        💡 Renewal of Domain and Hosting (cretapackaging.com) for 1 year. Target Expiry Boundary: 25/01/2024.
                    </td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Domain</td>
                    <td>cretapackaging.com</td>
                    <td class="text-center">1 Year</td>
                    <td class="text-end">₹1,800</td>
                    <td class="text-end fw-bold">₹1,800</td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Hosting</td>
                    <td>cretapackaging.com (Reseller Array)</td>
                    <td class="text-center">1 Year</td>
                    <td class="text-end">₹2,000</td>
                    <td class="text-end fw-bold">₹2,000</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 🗄️ BANKING TRANSACTIONS & SUMMARY METRIC ROWS -->
    <div class="container-fluid my-5 px-0">
        <div class="row g-4">
            <!-- Left Side: Secure Bank Remittance Data -->
            <div class="col-sm-7">
                <div class="banking-panel shadow-sm">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 1rem;">🏦 Remittance Banking Instructions</h5>
                    <table class="table table-sm table-borderless m-0 small">
                        <tr><td class="text-muted py-1" style="width: 40%;">Account Holder:</td><td class="fw-bold text-dark py-1">Nidhi Trivedi</td></tr>
                        <tr><td class="text-muted py-1">Account Number:</td><td class="fw-bold text-primary py-1" style="letter-spacing:0.5px;">3139484379</td></tr>
                        <tr><td class="text-muted py-1">Bank Name:</td><td class="fw-semibold text-dark py-1">State Bank of India</td></tr>
                        <tr><td class="text-muted py-1">Branch Code:</td><td class="text-dark py-1">Jagatpuri, Delhi-110051</td></tr>
                        <tr><td class="text-muted py-1">IFSC Code:</td><td class="fw-bold text-dark py-1">CBIN0281278</td></tr>
                    </table>
                </div>
            </div>
            
            <!-- Right Side: Sum totals Calculations -->
            <div class="col-sm-5 text-end">
                <div class="ms-auto" style="max-width: 300px;">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted text-start py-2">Subtotal:</td>
                            <td class="text-dark fw-semibold py-2">₹3,800</td>
                        </tr>
                        <tr>
                            <td class="text-muted text-start py-2">Tax Matrix (0%):</td>
                            <td class="text-dark py-2">₹0</td>
                        </tr>
                        <tr>
                            <td class="text-muted text-start py-2">Adjusted Discount:</td>
                            <td class="text-danger py-2">-₹300</td>
                        </tr>
                        <tr class="border-top" style="border-color: #e2e8f0 !important;">
                            <td class="text-dark fw-bold text-start py-3" style="font-size: 1.1rem;">Grand Total:</td>
                            <td class="text-primary fw-bold py-3" style="font-size: 1.1rem;">₹3,500</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 📜 SYSTEM SIGNATURE CLOSING OVERLAYS -->
    <div class="mt-5">
        <div class="thankyou-banner mb-3 shadow-sm">
            Thank You For Your Valued Business!
        </div>
        <p class="text-center text-muted small mt-2">
            ℹ️ *This is a system authenticated transaction manifest record. Explicit physical signature criteria are waived.*
        </p>
    </div>

</div>

<!-- ⚙️ COMPILATION SCRIPTS STORAGE -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
<script src="http://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<!-- ⚙️ Link your custom script matrix engine last -->
<script src="<?= Yii::$app->request->baseUrl ?>/js/invoice-engine.js"></script>
</body>
</html>