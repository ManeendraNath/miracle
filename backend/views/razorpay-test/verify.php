<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $orderId */
/** @var string $paymentId */
/** @var bool $signatureValid */
/** @var array|null $payment */
/** @var string|null $error */

$this->title = 'Razorpay Test Result';
$this->params['breadcrumbs'][] = ['label' => 'Razorpay Test', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Result';
$this->params['actions'][] = Html::a('<i class="fas fa-redo mr-1"></i> Another test', ['index'], ['class' => 'btn btn-primary']);

$captured = ($payment['status'] ?? null) === 'captured';
?>

<div class="card card-outline <?= $signatureValid && $captured ? 'card-success' : 'card-danger' ?> shadow-sm">
    <div class="card-header">
        <h3 class="card-title">
            <?php if ($signatureValid && $captured): ?>
                <i class="fas fa-check-circle text-success mr-1"></i> Payment captured and verified
            <?php elseif ($signatureValid): ?>
                <i class="fas fa-exclamation-triangle text-warning mr-1"></i> Signature valid, but Razorpay reports the payment as not captured
            <?php else: ?>
                <i class="fas fa-times-circle text-danger mr-1"></i> Signature check failed
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Order</dt><dd class="col-sm-8"><code><?= Html::encode($orderId ?: '-') ?></code></dd>
            <dt class="col-sm-4">Payment</dt><dd class="col-sm-8"><code><?= Html::encode($paymentId ?: '-') ?></code></dd>
            <dt class="col-sm-4">Checkout signature</dt><dd class="col-sm-8"><?= $signatureValid ? 'Valid (key secret matches)' : 'Invalid or missing' ?></dd>
            <?php if ($payment !== null): ?>
                <dt class="col-sm-4">Status at Razorpay</dt><dd class="col-sm-8"><?= Html::encode((string) ($payment['status'] ?? '-')) ?></dd>
                <dt class="col-sm-4">Amount</dt><dd class="col-sm-8"><?= Yii::$app->formatter->asCurrency(((int) ($payment['amount'] ?? 0)) / 100, 'INR') ?></dd>
                <dt class="col-sm-4">Method</dt><dd class="col-sm-8"><?= Html::encode((string) ($payment['method'] ?? '-')) ?></dd>
                <dt class="col-sm-4">Test mode</dt><dd class="col-sm-8"><?= empty($payment['livemode']) ? 'Yes' : 'No' ?></dd>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <dt class="col-sm-4">Lookup error</dt><dd class="col-sm-8 text-danger"><?= Html::encode($error) ?></dd>
            <?php endif; ?>
        </dl>
        <p class="text-muted small mt-3 mb-0">
            If a webhook is configured, Razorpay also sends <code>payment.captured</code> for this order;
            the app answers "ignored" because no invoice owns it. Nothing was stored.
        </p>
    </div>
</div>
