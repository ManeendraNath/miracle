<?php

declare(strict_types=1);

use backend\controllers\ProductOrderController;
use common\helpers\AdminIndexHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\ProductOrder;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array<int, string> $products */
/** @var int $product */
/** @var string $state */

$this->title = 'Store Sales';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Orders' => 'Each order is one licence of a theme or plugin, with its own invoice. It is paid when the invoice is: a UPI or bank proof approved, or a Razorpay or PayPal payment.',
    'Refunds' => 'Full refund if asked before the first download (shown here); after it, only if the product does not work as described and we cannot fix it. Refund the whole payment on its invoice and tick close invoice: any refund, even part, stops the licence key and download.',
    'Services' => 'A paid fixed-price service opens a support ticket with the buyer\'s brief. Do the job there, and resolve the ticket when it is delivered.',
    'Care plans' => 'A paid first month starts a monthly maintenance service (linked here) and opens a ticket for access. Later months bill through the usual maintenance renewals. To end a plan, cancel its maintenance; closing its first invoice as refunded cancels it too.',
    'Updates' => 'Buyers always download the latest file. Upload a new one in Themes & Plugins and tick "Email buyers about this update".',
];
GridViewHelper::register($this);
$fmt = Yii::$app->formatter;
?>
<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('Every order of a theme or plugin licence.', null, null) ?>
    <div class="card-body border-bottom">
        <?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 align-items-end']) ?>
            <div class="col-sm-5 col-lg-4">
                <label class="form-label small mb-1" for="filter-product">Product</label>
                <?= Html::dropDownList('product', $product ? (string) $product : null, $products, ['id' => 'filter-product', 'class' => 'form-select form-select-sm', 'prompt' => 'All products']) ?>
            </div>
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small mb-1" for="filter-state">Status</label>
                <?= Html::dropDownList('state', $state ?: null, ProductOrderController::STATES, ['id' => 'filter-state', 'class' => 'form-select form-select-sm', 'prompt' => 'All']) ?>
            </div>
            <div class="col-auto"><?= Html::submitButton('Show', ['class' => 'btn btn-sm btn-primary']) ?></div>
        <?= Html::endForm() ?>
    </div>
    <div class="card-body p-0">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'layout' => GridPaginationHelper::gridLayout(),
            'emptyText' => 'No orders yet.',
            'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
            'columns' => [
                ['label' => 'Ordered', 'value' => static fn(ProductOrder $o): string => $fmt->asDate($o->created_at, 'medium')],
                [
                    'label' => 'Product',
                    'format' => 'raw',
                    'value' => static fn(ProductOrder $o): string => Html::encode($o->product->name) . '<div class="small text-body-secondary">' . Html::encode($o->licenceLabel()) . ' licence</div>',
                ],
                [
                    'label' => 'Customer',
                    'format' => 'raw',
                    'value' => static fn(ProductOrder $o): string => Html::a(Html::encode((string) $o->user->username), ['/user/view', 'id' => $o->user_id]) . '<div class="small text-body-secondary">' . Html::encode((string) $o->user->email) . '</div>',
                ],
                [
                    'label' => 'Invoice',
                    'format' => 'raw',
                    'value' => static fn(ProductOrder $o): string => Html::a(Html::encode($o->invoice->invoice_number), ['/invoice/view', 'number' => $o->invoice->invoice_number]),
                ],
                [
                    'label' => 'Status',
                    'format' => 'raw',
                    'value' => static fn(ProductOrder $o): string => match ($o->state()) {
                        'paid' => '<span class="badge bg-success">Paid ' . $fmt->asDate($o->paid_at, 'medium') . '</span>',
                        'checking' => '<span class="badge bg-info text-dark">Proof to check</span>',
                        'due' => '<span class="badge bg-warning text-dark">Awaiting payment</span>',
                        'refunded' => '<span class="badge bg-secondary">Refunded</span>',
                        default => '<span class="badge bg-secondary">Cancelled</span>',
                    },
                ],
                [
                    'label' => 'Licence key / job',
                    'format' => 'raw',
                    'value' => static fn(ProductOrder $o): string => match (true) {
                        // Software: the key, where it works (changeable) and its subscription.
                        $o->product->isSoftware() && (bool) $o->licence_key => '<code>' . Html::encode($o->licence_key) . '</code>'
                            . '<div class="small">' . ($o->licenceActive() ? 'Active' : 'Ended') . ($o->maintenance !== null ? ' · ' . Html::a('subscription #' . (int) $o->maintenance->id, ['/maintenance/view', 'id' => $o->maintenance->id]) : '')
                            . ($o->licence_checked_at ? ' · last checked ' . Yii::$app->formatter->asDate($o->licence_checked_at, 'medium') : ' · never checked') . '</div>'
                            . Html::beginForm(['site', 'id' => $o->id], 'post', ['class' => 'd-flex gap-1 mt-1'])
                            . Html::textInput('site', (string) $o->licence_site, ['class' => 'form-control form-control-sm', 'aria-label' => 'Web address the licence works on', 'placeholder' => 'billing.example.com'])
                            . Html::submitButton('Move', ['class' => 'btn btn-sm btn-outline-secondary'])
                            . Html::endForm(),
                        $o->maintenance !== null => Html::a('Maintenance #' . (int) $o->maintenance->id, ['/maintenance/view', 'id' => $o->maintenance->id]) . ($o->supportTicket !== null ? '<div class="small">' . Html::a('Ticket ' . Html::encode($o->supportTicket->ticket_number), ['/support-ticket/view', 'number' => $o->supportTicket->ticket_number]) . '</div>' : ''),
                        $o->supportTicket !== null => Html::a('Ticket ' . Html::encode($o->supportTicket->ticket_number), ['/support-ticket/view', 'number' => $o->supportTicket->ticket_number]) . '<div class="small text-body-secondary">' . Html::encode($o->supportTicket->status) . '</div>',
                        (bool) $o->licence_key => '<code>' . Html::encode($o->licence_key) . '</code>',
                        default => '<span class="text-body-secondary">–</span>',
                    },
                ],
                [
                    'label' => 'First download',
                    'value' => static fn(ProductOrder $o): string => !$o->product->hasLicence() ? '(' . strtolower(\common\models\Product::KINDS[$o->product->kind]) . ')' : ($o->first_downloaded_at ? $fmt->asDatetime($o->first_downloaded_at, 'medium') : 'Not yet (full refund possible)'),
                    'contentOptions' => ['class' => 'small'],
                ],
            ],
        ]) ?>
    </div>
</div>
