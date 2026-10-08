<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */
/** @var yii\bootstrap5\ActiveForm $form */

$this->title = 'Submit UPI / Bank Transfer Proof: #' . $model->invoice_id;
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Invoice #' . $model->invoice_id, 'url' => ['view', 'id' => $model->invoice_id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="upi-payment-form-container my-5">
    <div class="row">
        <!-- Left Column: Display Static Business Accounts Mapping Parameters -->
        <div class="col-md-5 mb-4">
            <div class="card border-0 bg-light shadow-sm text-dark h-100">
                <div class="card-body p-4">
                    <h4 class="card-title fw-bold text-primary mb-3">Company Payment Details</h4>
                    <p class="text-muted small">Please execute your payment to the company accounts below, copy your transaction reference number, and upload the screenshot proof.</p>
                    
                    <hr class="border-secondary my-3">
                    
                    <div class="mb-3">
                        <label class="text-muted d-block small mb-1">Direct Corporate UPI ID</label>
                        <span class="fs-5 fw-bold text-dark">miracleweb@okaxis</span>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted d-block small mb-1">Bank Account Transfer (IMPS/NEFT)</label>
                        <span class="d-block fw-bold text-dark">Miracle Web Technologies</span>
                        <span class="d-block text-secondary small">Account No: 919654511842</span>
                        <span class="d-block text-secondary small">IFSC Code: UTIB0001234 (Axis Bank)</span>
                    </div>

                    <div class="alert alert-warning border-0 small mt-4 mb-0" role="alert">
                        <strong>Note:</strong> Manual transaction verifications typically take 1 to 12 hours to process against banking statements.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Interactive Upload File Input Form Elements -->
        <div class="col-md-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Upload Transaction Details</h4>
                    
                    <?php $form = ActiveForm::begin([
                        'id' => 'upi-upload-form',
                        'options' => ['enctype' => 'multipart/form-data'] // Mandatory context marker for processing multipart binary streams
                    ]); ?>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-muted small">Total Payable Amount</label>
                            <div class="form-control-plaintext fs-4 fw-bold text-success py-0">
                                ₹<?= number_format($model->amount, 2) ?>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <?= $form->field($model, 'gateway_payment_id')
                                ->textInput(['placeholder' => 'Enter 12-digit UPI Ref / UTR No or IMPS Ref Number', 'class' => 'form-control form-control-lg'])
                                ->label('Transaction ID / UTR Code', ['class' => 'form-label fw-bold'])
                                ->hint('Please cross-verify digits precisely to avoid reconciliation delays.') ?>
                        </div>

                        <div class="col-md-12 mb-3">
                            <?= $form->field($model, 'user_payment_notes')
                                ->textarea(['rows' => 3, 'placeholder' => 'Example: Transferred from Rahul Kumar Axis Account wallet bank app...'])
                                ->label('Sender Remarks / Notes (Optional)', ['class' => 'form-label fw-bold']) ?>
                        </div>

                        <div class="col-md-12 mb-4">
                            <?= $form->field($model, 'receiptFile')->fileInput([
                                'class' => 'form-control',
                                'accept' => 'image/*,application/pdf'
                            ])->label('Upload Receipt Screenshot (Image / PDF)', ['class' => 'form-label fw-bold'])
                              ->hint('Supported formats: JPG, JPEG, PNG, PDF (Max size: 5MB)') ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?= Html::submitButton('Submit Verification Proof', ['class' => 'btn btn-primary btn-lg fw-bold px-4']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
