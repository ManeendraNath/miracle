<?php

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
        <h1 class="h3 mb-0 text-gray-800"><?= Html::encode($this->title) ?></h1>
        <div class="action-buttons">
            <?php if ($model->status === 'Pending'): ?>
                <?= Html::a('<i class="fas fa-check-circle me-1"></i> Approve Payment', ['approve', 'id' => $model->id], [
                    'class' => 'btn btn-success fw-bold px-3 me-2',
                    'data' => [
                        'confirm' => 'Are you sure you want to verify this manual UPI reference and mark the invoice as PAID?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?= Html::a('<i class="fas fa-times-circle me-1"></i> Reject / Fail', ['reject', 'id' => $model->id], [
                    'class' => 'btn btn-danger fw-bold px-3',
                    'data' => [
                        'confirm' => 'Are you sure you want to reject this payment proof and mark it as FAILED?',
                        'method' => 'post',
                    ],
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-striped table-bordered detail-view mb-0 align-middle'],
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'invoice_id',
                                'format' => 'raw',
                                'value' => function($model) {
                                    return Html::a('#' . $model->invoice_id, ['invoice/view', 'id' => $model->invoice_id], ['class' => 'fw-bold']);
                                }
                            ],
                            [
                                'attribute' => 'gateway_id',
                                'label' => 'Payment Method',
                                'value' => function($model) {
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
                                'value' => function($model) {
                                    return '₹' . number_format($model->amount, 2);
                                }
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function($model) {
                                    $classMap = ['Pending' => 'bg-warning', 'Captured' => 'bg-success', 'Failed' => 'bg-danger'];
                                    $class = $classMap[$model->status] ?? 'bg-secondary';
                                    return Html::tag('span', $model->status, ['class' => "badge $class px-3 py-2 rounded-pill"]);
                                }
                            ],
                            'user_payment_notes:ntext',
                            [
                                'attribute' => 'paid_at',
                                'value' => function($model) {
                                    return $model->paid_at ? date('Y-m-d H:i:s', $model->paid_at) : 'Not Captured';
                                }
                            ],
                            'created_at:datetime',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold text-secondary mb-0"><i class="fas fa-paperclip me-2"></i>Uploaded Receipt Proof</h5>
                </div>
                <div class="card-body text-center py-4">
                    <?php if (!empty($model->payment_receipt_file)): ?>
                        <?php 
                        // Resolve domain routing URL link paths securely
                        $fileUrl = Yii::$app->urlManagerFrontend->createUrl($model->payment_receipt_file);
                        $isPdf = strtolower(pathinfo($model->payment_receipt_file, PATHINFO_EXTENSION)) === 'pdf';
                        ?>
                        
                        <?php if ($isPdf): ?>
                            <div class="mb-3"><i class="fas fa-file-pdf text-danger fa-4x"></i></div>
                            <?= Html::a('<i class="fas fa-download me-1"></i> Open Receipt PDF', $fileUrl, ['target' => '_blank', 'class' => 'btn btn-outline-primary btn-sm fw-bold px-3']) ?>
                        <?php else: ?>
                            <a href="<?= $fileUrl ?>" target="_blank" title="Click to open full version">
                                <?= Html::img($fileUrl, ['class' => 'img-fluid rounded border shadow-sm mb-3', 'style' => 'max-height: 250px; object-fit: contain;']) ?>
                            </a>
                            <br>
                            <?= Html::a('<i class="fas fa-search-plus me-1"></i> View Full Screen', $fileUrl, ['target' => '_blank', 'class' => 'btn btn-light btn-sm text-secondary border fw-bold']) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted py-4">
                            <i class="fas fa-image fa-3x mb-3 text-gray-300 d-block"></i>
                            <span class="small">No receipt snapshot file was uploaded for this transaction ledger row.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
