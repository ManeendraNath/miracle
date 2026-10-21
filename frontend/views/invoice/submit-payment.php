<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Submit Payment';

$this->params['breadcrumbs'][] = ['label' => 'My Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = [
    'label' => $invoice->invoice_number,
    'url' => ['view', 'number' => $invoice->invoice_number],
];
$this->params['breadcrumbs'][] = $this->title;

$payment = Yii::$app->params['billing']['payment'] ?? [];
?>
<div class="card bg-light border mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Bank / UPI Payment Details</h6>
        <?php if (!empty($payment['upiId'])): ?>
            <div class="mb-2">
                <strong>UPI ID:</strong>
                <?= Html::encode($payment['upiId']) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment['accountName'])): ?>
            <div>
                <strong>Account Name:</strong>
                <?= Html::encode($payment['accountName']) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment['bankName'])): ?>
            <div>
                <strong>Bank:</strong>
                <?= Html::encode($payment['bankName']) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment['accountNumber'])): ?>
            <div>
                <strong>Account Number:</strong>
                <?= Html::encode($payment['accountNumber']) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($payment['ifsc'])): ?>
            <div>
                <strong>IFSC:</strong>
                <?= Html::encode($payment['ifsc']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Submit UPI / Bank Transfer Payment</strong>
        </div>
        <div class="card-body">
            <div class="alert alert-light border">
                <div>
                    <strong>Invoice:</strong> <?= Html::encode($invoice->invoice_number) ?>
                </div>
                <div>
                    <strong>Outstanding:</strong> <?= Yii::$app->formatter->asCurrency($outstandingAmount, 'INR') ?>
                </div>
            </div>
            <?php
            $form = ActiveForm::begin(['id' => 'payment-proof-form', 'options' => ['enctype' => 'multipart/form-data']]);
            ?>
            <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
            <?= $form->field($model, 'gateway_payment_id')->textInput(['maxlength' => true, 'autocomplete' => 'off', 'placeholder' => 'UPI / UTR / bank transaction reference']) ?>
            <?= $form->field($model, 'receiptFile')->fileInput(['accept' => '.jpg,.jpeg,.png,.pdf'])->hint('JPG, PNG or PDF. Maximum file size 5 MB.')
            ?>
            <?= $form->field($model, 'user_payment_notes')->textarea(['rows' => 4, 'placeholder' => 'Optional payment remarks']) ?>
            <div class="d-flex gap-2">
                <?= Html::submitButton('Submit Payment Proof', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Cancel', ['view', 'number' => $invoice->invoice_number], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>