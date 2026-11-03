<?php

use common\models\Invoice;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var float $subtotal */
/** @var float $cgstAmount */
/** @var float $sgstAmount */
/** @var float $igstAmount */
/** @var float $grandTotal */
/** @var float $capturedAmount */
/** @var float $outstandingAmount */
$this->title = 'Invoice #' . $model->invoice_number;
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Review client billing metrics or compile print configurations';
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border'])];
if ($model->status === Invoice::STATUS_UNPAID) {
    $this->params['actions'][] = Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']);
    $this->params['actions'][] = Html::a('<i class="fas fa-ban me-1"></i> Cancel Invoice', ['cancel', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'data' => [
            'confirm' => 'Are you sure you want to cancel this invoice?',
            'method' => 'post',
        ],
    ]);
    $this->params['actions'][] = Html::a('Resend Invoice', ['resend-invoice', 'id' => $model->id], [
        'class' => 'btn btn-outline-primary',
        'data' => [
            'method' => 'post',
            'confirm' => 'Send this invoice to the customer again?',
        ],
    ]);
}
$this->params['actions'][] = Html::button('<i class="fas fa-print me-1"></i> Print / Save PDF', ['class' => 'btn btn-outline-secondary', 'onclick' => 'window.print();']);
?>

<div class="container-fluid py-4">
    <?= $this->render('_invoice_content', ['model' => $model, 'capturedAmount' => $capturedAmount, 'outstandingAmount' => $outstandingAmount]) ?>

    <?php
    $transactions = $model->transactions;
    ?>
    <div class="card card-outline card-info shadow-sm mt-4">
        <div class="card-header">
            <h3 class="card-title">Payment Activity</h3>
        </div>
        <div class="card-body">
<?php if (!$transactions): ?>
                <div class="text-muted">
                    No payment records available.
                </div>
<?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= Html::encode($transaction->gateway_payment_id) ?></td>
                                <td><?= Html::encode($transaction->status) ?></td>
                                <td><?= Yii::$app->formatter->asCurrency($transaction->amount, 'INR') ?></td>
                                <td><?= $transaction->payment_receipt_file ? Html::a('View Receipt', ['/transaction/receipt', 'id' => $transaction->id], ['target' => '_blank', 'class' => 'btn btn-sm btn-info']) : '<span class="text-muted">N/A</span>' ?></td>
                            </tr>
    <?php endforeach; ?>
                    </tbody>
                </table>
<?php endif; ?>
        </div>
    </div>
</div>

<!-- NATIVE CSS MEDIA PRINT OVERRIDES OVERLAY STYLE RULES -->
<style>
    @media print {
        .no-print, .content-header, .main-footer, .brand-link, .main-header, .sidebar, .nav {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        .invoice {
            border: none !important;
            padding: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        body {
            background-color: #ffffff !important;
            font-size: 12px !important;
        }
        .badge {
            border: 1px solid #999 !important;
            color: #000 !important;
            background: transparent !important;
        }
    }
</style>
