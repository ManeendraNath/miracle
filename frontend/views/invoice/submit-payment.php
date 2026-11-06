<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Submit Payment';

$this->params['breadcrumbs'][] = ['label' => 'My Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = [
    'label' => $invoice->invoice_number,
    'url' => ['view', 'number' => $invoice->invoice_number],
];
$this->params['breadcrumbs'][] = $this->title;

$payment = Yii::$app->params['billing']['payment'] ?? [];
$bankDetails = array_filter([
    'UPI ID' => $payment['upiId'] ?? '',
    'Account Name' => $payment['accountName'] ?? '',
    'Bank' => $payment['bankName'] ?? '',
    'Account Number' => $payment['accountNumber'] ?? '',
    'IFSC' => $payment['ifsc'] ?? '',
]);
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Submit Payment</h1>
    <div class="text-body-secondary">Paid by UPI or bank transfer? Send us the reference and receipt so we can verify it.</div>
</div>

<p class="small text-body-secondary">Outside India? <?= Html::a('Pay online from the invoice page', ['view', 'number' => $invoice->invoice_number]) ?> with an international card. If your card is declined, <?= Html::a('raise a support ticket', ['/support/create', 'subject' => 'Paying from outside India']) ?>.</p>

<div class="row g-4">
    <div class="col-xl-8 order-2 order-xl-1">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Submit UPI / Bank Transfer Payment</h2>
            </div>
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(['id' => 'payment-proof-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'gateway_payment_id')->textInput(['maxlength' => true, 'autocomplete' => 'off', 'placeholder' => 'UPI / UTR / bank transaction reference']) ?>
                <?= $form->field($model, 'receiptFile')->fileInput(['accept' => '.jpg,.jpeg,.png,.pdf'])->hint('JPG, PNG or PDF. Maximum file size 5 MB.') ?>
                <?= $form->field($model, 'user_payment_notes')->textarea(['rows' => 4, 'placeholder' => 'Optional payment remarks']) ?>
                <div class="d-flex flex-wrap gap-2">
                    <?= Html::submitButton('<i class="fas fa-paper-plane me-1"></i> Submit Payment Proof', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Cancel', ['view', 'number' => $invoice->invoice_number], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4 order-1 order-xl-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="small text-body-secondary">Outstanding on <?= Html::a(Html::encode($invoice->invoice_number), ['view', 'number' => $invoice->invoice_number], ['class' => 'fw-semibold text-decoration-none']) ?></div>
                <div class="display-6 fw-bold"><?= Yii::$app->formatter->asCurrency($outstandingAmount, 'INR') ?></div>

                <?php if ($bankDetails): ?>
                    <hr>
                    <div class="small text-uppercase text-body-secondary fw-semibold mb-2">Bank / UPI Payment Details</div>
                    <dl class="small mb-0">
                        <?php foreach ($bankDetails as $label => $value): ?>
                            <dt class="fw-normal text-body-secondary"><?= $label ?></dt>
                            <dd class="fw-semibold text-break mb-2"><?= Html::encode($value) ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
