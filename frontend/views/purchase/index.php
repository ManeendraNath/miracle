<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var \common\models\ProductOrder[] $orders */

$this->title = 'My Purchases';
$this->params['breadcrumbs'][] = $this->title;
$fmt = Yii::$app->formatter;
?>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">My Purchases</h1>
    <div class="text-body-secondary">Themes, plugins and fixed-price services you bought: licence keys, downloads and job tickets.</div>
</div>

<?php if (!$orders): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <p class="text-body-secondary mb-3">You have not bought anything from the store yet.</p>
            <?= Html::a('Browse themes and plugins', ['/store/index'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($orders as $order): ?>
    <?php $state = $order->state(); ?>
    <?php $which = '<span class="visually-hidden">: ' . Html::encode($order->product->name) . '</span>'; ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-2">
                <div>
                    <h2 class="h5 mb-1"><?= Html::encode($order->product->name) ?></h2>
                    <div class="small text-body-secondary">
                        <?= $order->product->isService() ? 'Fixed-price service' : Html::encode($order->licenceLabel()) . ' licence' ?> · ordered <?= $fmt->asDate($order->created_at, 'medium') ?>
                        · invoice <?= Html::a(Html::encode($order->invoice->invoice_number), ['/invoice/view', 'number' => $order->invoice->invoice_number]) ?>
                    </div>
                </div>
                <div>
                    <?php if ($state === 'paid'): ?>
                        <span class="badge bg-success-subtle text-success-emphasis">Paid <?= $fmt->asDate($order->paid_at, 'medium') ?></span>
                    <?php elseif ($state === 'due'): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis">Awaiting payment</span>
                    <?php elseif ($state === 'checking'): ?>
                        <span class="badge bg-info-subtle text-info-emphasis">Checking your payment</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= $state === 'refunded' ? 'Refunded' : 'Cancelled' ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($state === 'paid' && $order->product->isService()): ?>
                <?php $ticket = $order->supportTicket; ?>
                <p class="small mb-2">
                    <?php if ($ticket === null): ?>
                        We are setting up the job and will contact you shortly.
                    <?php elseif (in_array($ticket->status, [\common\models\SupportTicket::STATUS_RESOLVED, \common\models\SupportTicket::STATUS_CLOSED], true)): ?>
                        <strong>Delivered.</strong> Anything to change? Reply in the ticket.
                    <?php elseif ($ticket->status === \common\models\SupportTicket::STATUS_IN_PROGRESS): ?>
                        <strong>In progress.</strong> Due within <?= (int) $order->product->delivery_days ?> working days of payment and of anything we asked you for.
                    <?php else: ?>
                        <strong>Paid: we start shortly.</strong> We may ask you for access or details in the ticket.
                    <?php endif; ?>
                </p>
                <?php if ($ticket !== null): ?>
                    <?= Html::a('Open the job ticket' . $which, ['/support/view', 'number' => $ticket->ticket_number], ['class' => 'btn btn-primary btn-sm']) ?>
                <?php endif; ?>
            <?php elseif ($state === 'paid'): ?>
                <p class="small mb-3">
                    <?= $order->product->version ? 'Latest version ' . Html::encode($order->product->version) . ', free to download. ' : 'Updates are free to download. ' ?>
                    <?= $order->supportUntil() >= time() ? 'Setup help and support until ' . $fmt->asDate($order->supportUntil(), 'medium') . '.' : 'Support ended on ' . $fmt->asDate($order->supportUntil(), 'medium') . '; updates stay free.' ?>
                    <?= Html::a('Licence terms', ['/site/licence']) ?>
                </p>
                <div class="mb-3">
                    <label class="form-label small text-body-secondary mb-1" for="key-<?= $order->id ?>">Licence key</label>
                    <input type="text" readonly class="form-control font-monospace" id="key-<?= $order->id ?>" value="<?= Html::encode($order->licence_key) ?>">
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($order->product->download_file): ?>
                        <?= Html::a('<i class="fas fa-download me-2" aria-hidden="true"></i>Download' . $which, ['download', 'id' => $order->id], ['class' => 'btn btn-primary btn-sm', 'data-pjax' => 0]) ?>
                    <?php else: ?>
                        <span class="small text-body-secondary align-self-center">We will send you the files shortly.</span>
                    <?php endif; ?>
                    <?= Html::a('Get setup help' . $which, ['/support/create', 'subject' => 'Setup help: ' . $order->product->name], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                </div>
            <?php elseif ($state === 'checking'): ?>
                <p class="small text-body-secondary mb-0">We received your payment proof and are checking it. <?= $order->product->isService() ? 'We open the job ticket' : 'Your licence key and download appear here' ?> once it is confirmed.</p>
            <?php elseif ($state === 'due'): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?= Html::a('Pay now' . $which, ['/invoice/view', 'number' => $order->invoice->invoice_number], ['class' => 'btn btn-primary btn-sm']) ?>
                    <?= Html::a('Cancel order' . $which, ['cancel', 'id' => $order->id], ['class' => 'btn btn-outline-danger btn-sm', 'data' => ['method' => 'post', 'confirm' => 'Cancel this order? Its invoice will be cancelled too.']]) ?>
                </div>
                <p class="small text-body-secondary mt-2 mb-0"><?= $order->product->isService() ? 'We open the job ticket' : 'Your licence key and download appear here' ?> once the payment is confirmed. Payments by UPI or bank transfer are confirmed once our team checks the proof.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
