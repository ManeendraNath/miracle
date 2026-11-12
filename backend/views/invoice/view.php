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
$this->params['subtitle'] = 'The invoice, its payments, and what you can do with it.';
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border'])];
if ($model->status === Invoice::STATUS_PARTIALLY_PAID) {
    $this->params['actions'][] = Html::a('<i class="fas fa-times-circle me-1"></i> Write Off Balance', ['close', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'title' => 'Forgive what is left: money already received is kept. It shows as Closed (balance written off).',
        'data' => [
            'confirm' => 'Write off the remaining balance? Money already received is kept and nothing more is owed on this invoice or on balances carried into it. It shows as Closed (balance written off). Its unpaid renewals are billed again in full on the next run, with no credit for money kept, unless those services are disabled.',
            'method' => 'post',
        ],
    ]);
}
if ($model->status === Invoice::STATUS_DRAFT) {
    $this->params['actions'][] = Html::a('<i class="fas fa-paper-plane me-1"></i> Send to customer', ['release', 'id' => $model->id], [
        'class' => 'btn btn-success',
        'data' => ['confirm' => 'Send this invoice to the customer now? They will see it in the portal and get it by email, dated today and due on the expiry date shown.', 'method' => 'post'],
    ]);
    // Renewal lines can't be edited by hand (InvoiceController::actionUpdate refuses them).
    if (!\common\models\BillingEvent::find()->where(['invoice_id' => $model->id])->exists()) {
        $this->params['actions'][] = Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']);
    }
    $this->params['actions'][] = Html::a('<i class="fas fa-ban me-1"></i> Cancel Invoice', ['cancel', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'data' => [
            'confirm' => 'Cancel this invoice? Any renewal on it will be billed again (by the next billing run if the service is Active). To stop that, make the service inactive as well.',
            'method' => 'post',
        ],
    ]);
}
if ($model->status === Invoice::STATUS_UNPAID) {
    $this->params['actions'][] = Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']);
    $this->params['actions'][] = Html::a('<i class="fas fa-ban me-1"></i> Cancel Invoice', ['cancel', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'data' => [
            'confirm' => 'Cancel this invoice? Any renewal on it will be billed again (by the next billing run if the service is Active). To stop that, make the service inactive as well.',
            'method' => 'post',
        ],
    ]);
    $this->params['actions'][] = Html::a('<i class="fas fa-times-circle me-1"></i> Write Off', ['close', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'title' => 'For an invoice that cannot be cancelled: nothing more is owed. It shows as Closed (balance written off).',
        'data' => [
            'confirm' => 'Write off this invoice? Nothing more is owed on it or on balances carried into it, and it shows as Closed (balance written off). Money kept on those balances stays. Its unpaid renewals are billed again in full on the next run, with no credit for money kept, unless those services are disabled.',
            'method' => 'post',
        ],
    ]);
}
if (in_array($model->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
    $this->params['actions'][] = Html::a('Resend Invoice', ['resend-invoice', 'id' => $model->id], [
        'class' => 'btn btn-outline-primary',
        'data' => [
            'method' => 'post',
            'confirm' => 'Send this invoice to the customer again?',
        ],
    ]);
}
if ($model->status !== Invoice::STATUS_DRAFT && !str_starts_with((string) $model->invoice_number, 'TMP-')) {
    $this->params['actions'][] = Html::a('<i class="fas fa-envelope me-1"></i> Email customer', ['/user/email', 'id' => $model->user_id, 'invoice' => $model->id], ['class' => 'btn btn-outline-secondary']);
}
$this->params['actions'][] = Html::button('<i class="fas fa-print me-1"></i> Print / Save PDF', ['class' => 'btn btn-outline-secondary', 'id' => 'invoice-print']);
$this->registerJs('document.getElementById("invoice-print").addEventListener("click", () => window.print());');
$this->params['help'] = [
    'Send to customer' => 'Only on an invoice waiting for review. Approves it: it gets its invoice number, is emailed, and the customer can see and pay it. Any older invoice of this customer that is past its due date, was sent at least 15 days ago and has no payment waiting for review is added to it at that moment; if one is, this invoice can no longer be cancelled.',
    'Update' => 'Edit an invoice made by hand while nothing is paid on it. Renewal and carried-forward lines can\'t be edited. Cancel the invoice first (or Write Off where Cancel is refused, always the case once an older balance is carried in), then change the service: its price, expiry date or customer. It is invoiced again by a billing run once the service is Active and its expiry date is within the invoicing window.',
    'Cancel Invoice' => 'For an invoice that nothing has happened on yet. It is refused, with the reason shown, if money was received, an older balance is carried into it, a payment is waiting for review, or a renewal on it was already granted (for example after a Refund (Still Owed)). Approve or reject a waiting payment first; in the other cases use Write Off. The record is kept, marked Cancelled. Its renewals are billed again unless the service is made inactive: by the next billing run if the service is Active, or once a suspended or overdue service is set Active again.',
    'Write Off / Write Off Balance' => 'Nothing more is owed; money already received is kept. It shows as Closed (balance written off). Use it when an invoice should not be cancelled. Its unpaid renewals are billed again in full, with no credit for money kept, unless the service is disabled: by the next billing run if the service is Active, or once a suspended or overdue service is set Active again.',
    'Resend Invoice' => 'Emails the invoice to the customer again (unpaid or part-paid only).',
    'Email customer' => 'Write to this customer about this invoice, with ready-made templates.',
    'Print / Save PDF' => 'Opens the browser print window; choose Save as PDF there for a file.',
    'Payment Activity' => 'Every payment against this invoice, with refunds and receipts. Customers pay online, or upload UPI / bank transfer proof in their portal; open such a payment on the Transactions page to approve or reject it.',
];
?>

<div class="container-fluid py-4">
    <?php if ($model->status === Invoice::STATUS_DRAFT): ?>
        <div class="alert alert-warning">
            <strong>Waiting for review.</strong> The customer can't see or pay this invoice, and gets no email or reminder, until you send it. Check the lines, periods and total, then:
            <ul class="mb-0 mt-1">
                <li><strong>Approve:</strong> click <strong>Send to customer</strong>. It gets its invoice number, is emailed, and the customer can see and pay it. Any older invoice of this customer that is past its due date, was sent at least 15 days ago and has no payment waiting for review is added to it at that moment, so the total sent can be higher than shown here, and then it can no longer be cancelled.</li>
                <li><strong>Reject:</strong> click <strong>Cancel Invoice</strong>. The renewal is billed again unless the service is made inactive: by the next billing run if the service is Active, or once a suspended or overdue service is set Active again.</li>
                <li><strong>Wrong renewal price:</strong> renewal invoices can't be edited. Cancel this invoice, set the service's <strong>Next Renewal Price</strong>, and the next billing run invoices it again.</li>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($model->emailed_at === null && in_array($model->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)): ?>
        <div class="alert alert-warning">No email is recorded for this invoice, so the customer may not have received it. Check their email address, then use Resend Invoice.</div>
    <?php endif; ?>
    <?php $carriedTo = $model->status === Invoice::STATUS_CARRIED_FORWARD ? $model->carriedTo() : null; ?>
    <?php if ($carriedTo !== null): ?>
        <div class="alert alert-info">The balance moved to invoice <?= Html::a(Html::encode($carriedTo->invoice_number), ['view', 'number' => $carriedTo->invoice_number], ['class' => 'alert-link']) ?> (<?= Html::encode($carriedTo->statusLabel()) ?>).</div>
    <?php endif; ?>
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
                                <td>
                                    <?= Yii::$app->formatter->asCurrency($transaction->amount, 'INR') ?>
                                    <?= (float) $transaction->refunded_amount > 0 ? Html::tag('div', 'Refunded ' . Yii::$app->formatter->asCurrency($transaction->refunded_amount, 'INR'), ['class' => 'small text-danger']) : '' ?>
                                    <?= (int) $transaction->is_unapplied === 1 ? Html::tag('div', 'Unapplied', ['class' => 'badge bg-warning text-dark']) : '' ?>
                                </td>
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
