<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Bootstrap 5 Example</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            body {
                font-family: 'Quicksand', sans-serif;
                font-size:12px;
            }
            .sitename {
                font-family: 'Playfair Display', serif;
            }
            .thankyou {
                font-size: 36px;
                font-weight: bold;
                background-image: linear-gradient(#0f67de, #077bab);
                padding-top: 0px;
                padding-bottom: 5px;
                text-align:center;
                color:#fff;
            }
            table thead tr th {
                background-image: linear-gradient(#0f67de, #077bab);
                border-color:#fff;
            }
            .border {
                background-image: linear-gradient(#0f67de, #077bab);
                height:10px;
            }
            .bill-invoice {
                display:inline-block;
                float:left;
                margin-top:-15px;
                font-family: 'Playfair Display', serif;
                width:160px;
                height:170px;
                background:#fff;
                color:#333;
                font-size:35px;
                font-weight: bold;
                line-height: 40px;
                padding-top:35px;
                text-align: center;
            }
            .address {
                display:inline-block;
                float:right;
                margin-right:10px;
                color:#fff;
                height:176px;
            }
        </style>
    </head>
    <body id="pdf">

        <header class="bg-dark text-white" style="padding-top:10px;padding-bottom:2px;">
            <div class="bill-invoice">BILL INVOICE</div>
            <div class="address">
            <br/>A-1/9, Street No. 4, East Krishna Nagar,<br/>
            Delhi-110051<br/><br/>
            Contact : +91-9654511842<br/>
            E-mail : <a href="mailto:info@miraclewebtechnologies.com">info@miraclewebtechnologies.com</a><br/>
            Website : <a href="http://www.miraclewebtechnologies.com">http://www.miraclewebtechnologies.com</a>
            </div>
            <div class="sitename text-center">
                <img src="<?php echo yii\helpers\Url::base(); ?>/backend/web/images/logo.png" style="height:90px;margin-bottom:10px;" />
                <h6 style="margin-bottom:0px;">Miracle Web Technologies</h6>
                <p style="color:#077bab"><small><i>Upgrade your business with us</i></small></p>
            </div>
        </header>

        <div class="border"></div>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-sm-6">
                    <h3>Bill to</h3>
                    Creta Packaging,<br/>
                    New Delhi
                </div>
                <div class="col-sm-6 text-end">
                    <p><b>Date : <?= date("dS F, Y"); ?></b></p>
                    <p><b>Invoice # : MWT1011</b></p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Category</th>
                        <th>Discription</th>
                        <th>Duration/Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5">Renewal of Domain and Hosting (cretapackaging.com) for 1 year. This renewal will expire on 25/01/2024.</td></tr>
                    <tr>
                        <td>Domain</td>
                        <td>cretapackaging.com</td>
                        <td>1 Year</td>
                        <td>INR 1800/-</td>
                        <td>INR 1800/-  </td>
                    </tr>
                    <tr>
                        <td>Hosting</td>
                        <td>cretapackaging.com</td>
                        <td>1 Year</td>
                        <td>INR 2000/-</td>
                        <td>INR 2000/-  </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border"></div>
        <div class="container-fluid mt-3">
            <div class="row">
                <div class="col-sm-8">
                    <!--<img src="<?php echo yii\helpers\Url::base(); ?>/backend/web/images/paid.webp" style="height:120px" />-->
                    <p>Note : Please deposit the amount in favour of :</p>
                    <p><b>A/C Holder Name :</b> Nidhi Trivedi</p>
                    <p><b>A/C No. :</b> 3139484379</p>
                    <p><b>Bank Name :</b> State Bank of India</p>
                    <p><b>Branch :</b> Jagatpuri, Delhi-110051</p>
                    <p><b>IFSC Code :</b> CBIN0281278</p>
                </div>
                <div class="col-sm-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><td><b>Sub Total :</b></td><td class="text-end"> INR 3800/-</td></tr>
                            <tr><td><b>Tax(%) :</b></td><td class="text-end"> 0/-</td></tr>
                            <tr><td><b>Discount :</b></td><td class="text-end"> INR 300/-</td></tr>
                            <tr><td><b>Total :</b></td><td class="text-end"> <b>INR 3500/-</b></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="thankyou">Thank you for your business</div>
        <p class="text-center">* This is computer generated invoice no signature required.</p>
        <!-- Scripts down here -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
        <script src="http://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>

            $(document).ready(function () {
                generatePdf('pdf', 'Bill_Invoice_AnupamAyurveda_Renewal_1_year(<?= date("Y-m-d"); ?>).pdf');
            });

            function generatePdf(elementId, fileName) {
                var element = document.getElementById(elementId);
                var opt = {
                    margin: 0,
                    filename: fileName,
                    image: {type: 'jpeg', quality: 1.00},
                    html2canvas: {scale: 2},
                    jsPDF: {unit: 'in', format: 'a4', orientation: 'portrait'}
                };
                // New Promise-based usage:
                html2pdf().set(opt).from(element).save();
            }
        </script>
    </body>
</html>
