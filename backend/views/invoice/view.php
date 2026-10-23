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
?>

<div class="container-fluid py-4">
    <!-- Invoice Control Panel Header Card -->
    <div class="card card-outline card-success shadow no-print mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="m-0 fw-bold text-dark"><i class="fas fa-file-invoice text-success me-2"></i> Invoice Ledger Record</h4>
                <small class="text-muted">Review client billing metrics or compile print configurations</small>
            </div>
            <div class="d-flex flex-wrap gap-2">

                <?=
                Html::a(
                        '<i class="fas fa-arrow-left me-1"></i> Back to Grid',
                        ['index'],
                        ['class' => 'btn btn-sm btn-secondary']
                )
                ?>
                <?php if ($model->status === Invoice::STATUS_UNPAID): ?>
                    <?= Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>

                    <?=
                    Html::a(
                            '<i class="fas fa-ban me-1"></i> Cancel Invoice',
                            ['cancel', 'id' => $model->id],
                            [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data' => [
                                    'confirm' => 'Are you sure you want to cancel this invoice?',
                                    'method' => 'post',
                                ],
                            ]
                    )
                    ?>
                    <?=
                    Html::a(
                            'Resend Invoice',
                            ['resend-invoice', 'id' => $model->id],
                            [
                                'class' => 'btn btn-sm btn-outline-primary',
                                'data' => [
                                    'method' => 'post',
                                    'confirm' => 'Send this invoice to the customer again?',
                                ],
                            ]
                    )
                    ?>
                <?php endif; ?>
                <button type="button" onclick="window.print();" class="btn btn-sm btn-primary"><i class="fas fa-print me-1"></i> Print / Save PDF</button>
            </div>
        </div>
    </div>
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
        .no-print, .main-footer, .brand-link, .main-header, .sidebar, .nav {
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
