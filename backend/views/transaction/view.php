<?php

use common\models\Invoice;
use common\models\Transaction;
use yii\bootstrap5\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */
$this->title = 'Transaction Audit: #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="transaction-view container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="action-buttons">
            <?php if ($model->status === Transaction::STATUS_PENDING): ?>
                <?=
                Html::a('<i class="fas fa-check-circle me-1"></i> Approve Payment', ['approve', 'id' => $model->id], [
                    'class' => 'btn btn-sm btn-success px-3 me-2',
                    'data' => [
                        'confirm' => 'Confirm this payment after verifying the bank / UPI transaction?',
                        'method' => 'post',
                    ],
                ])
                ?>
                <?=
                Html::a('<i class="fas fa-times-circle me-1"></i> Reject / Fail', ['reject', 'id' => $model->id], [
                    'class' => 'btn btn-sm btn-danger px-3',
                    'data' => [
                        'confirm' => 'Reject this submitted payment proof?',
                        'method' => 'post',
                    ],
                ])
                ?>
                <?php if (!empty($model->payment_receipt_file)): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header">
                            <strong>Payment Proof</strong>
                        </div>
                        <div class="card-body">
                            <?= Html::a('View Receipt', ['receipt', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="card card-outline card-primary mb-8">
            <div class="card-header">
                <h3 class="card-title">Invoice Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Invoice Number</strong><br>
                        <?= Html::encode($model->invoice->invoice_number) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Client</strong><br>
                        <?= Html::encode($model->invoice->client_name) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Payable</strong><br>
                        <?= Yii::$app->formatter->asCurrency($model->invoice->total_payable, 'INR') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Status</strong><br>
                        <?= Html::encode(Invoice::getStatuses()[$model->invoice->status] ?? $model->invoice->status) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <?=
                    DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-striped table-bordered detail-view mb-0 align-middle'],
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'invoice_id',
                                'label' => 'Invoice',
                                'format' => 'raw',
                                'value' => static function (Transaction $model): string {
                                    if ($model->invoice === null) {
                                        return '-';
                                    }
                                    return Html::a(Html::encode($model->invoice->invoice_number), ['invoice/view', 'number' => $model->invoice->invoice_number], ['class' => 'fw-semibold']);
                                },
                            ],
                            [
                                'attribute' => 'gateway_id',
                                'label' => 'Payment Method',
                                'value' => function ($model) {
                                    $map = [1 => 'Razorpay (Automated)', 2 => 'Manual UPI / Bank Transfer'];
                                    return $map[$model->gateway_id] ?? 'Manual / Admin Credit';
                                }
                            ],
                            [
                                'attribute' => 'gateway_payment_id',
                                'label' => 'UTR / Transaction Reference No',
                                'options' => ['class' => 'fw-bold text-dark'],
                            ],
                            [
                                'attribute' => 'amount',
                                'value' => function ($model) {
                                    return '₹' . number_format($model->amount, 2);
                                }
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $classMap = ['Pending' => 'bg-warning', 'Captured' => 'bg-success', 'Failed' => 'bg-danger'];
                                    $class = $classMap[$model->status] ?? 'bg-secondary';
                                    return Html::tag('span', $model->status, ['class' => "badge $class px-3 py-2 rounded-pill"]);
                                }
                            ],
                            'user_payment_notes:ntext',
                            [
                                'attribute' => 'paid_at',
                                'value' => function ($model) {
                                    return $model->paid_at ? date('Y-m-d H:i:s', $model->paid_at) : 'Not Captured';
                                }
                            ],
                            'created_at:datetime',
                        ],
                    ])
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
