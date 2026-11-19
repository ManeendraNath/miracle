<?php

declare(strict_types=1);

use common\models\Product;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Product $model */
/** @var string $licence */
/** @var bool $usd buyer abroad: priced in US dollars, no GST */
/** @var bool $canPay false: a buyer abroad while PayPal is off */

$fmt = Yii::$app->formatter;
$price = (float) $model->price($licence, $usd);
$this->title = 'Your order: ' . $model->name;
$this->params['bare'] = true;
$this->params['robots'] = 'noindex, follow';
?>
<div class="store-buy">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <nav aria-label="breadcrumb" class="mb-3">
                <?= Html::a('Store', ['/store/index'], ['class' => 'mw-crumb']) ?>
                <span class="mx-2 opacity-50">/</span><?= Html::a(Html::encode($model->name), ['/store/view', 'slug' => $model->slug], ['class' => 'mw-crumb']) ?>
            </nav>
            <h1 class="mw-h1 mb-0">Your order</h1>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="mw-card p-4">
                        <div class="d-flex justify-content-between align-items-baseline gap-3 mb-1">
                            <h2 class="h5 mb-0"><?= Html::encode($model->name) ?></h2>
                            <p class="fw-bold fs-5 mb-0 text-nowrap"><?= $usd ? 'US$' . $fmt->asDecimal($price, 2) : '₹' . $fmt->asDecimal($price, 0) ?></p>
                        </div>
                        <p class="text-body-secondary mb-3">
                            <?= Html::encode($model->typeLabel()) ?>, <?= Html::encode(strtolower(Product::LICENCES[$licence])) ?> licence<?= $model->version ? ', version ' . Html::encode($model->version) : '' ?>
                        </p>
                        <ul class="list-unstyled small mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= $licence === 'single' ? 'Use on one website.' : 'Use on any number of your websites.' ?></li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>Our help to set it up, 6 months of support, and every new version free.</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= $usd ? 'Priced in US dollars for buyers outside India. No GST.' : 'GST is added on the invoice where it applies.' ?></li>
                        </ul>
                        <h3 class="h6">What happens next</h3>
                        <ol class="small mb-4 ps-3">
                            <li>We create your invoice and take you to it.</li>
                            <li><?= $usd ? 'Pay online by PayPal, with a card or a PayPal account.' : 'Pay by UPI or bank transfer and upload the payment proof, or pay online by card where offered.' ?></li>
                            <li>Once the payment is confirmed, your licence key and download appear on <strong>My Purchases</strong>.</li>
                        </ol>
                        <?php if ($canPay): ?>
                            <?= Html::beginForm() ?>
                                <?= Html::submitButton('Continue to payment', ['class' => 'btn btn-primary w-100']) ?>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0" role="status">
                                Online payment from outside India is not available right now.
                                <?= Html::a('Contact us', ['/site/contact', 'subject' => 'Buy ' . $model->name . ' (' . strtolower(Product::LICENCES[$licence]) . ' licence)']) ?> and we will arrange your payment.
                            </div>
                        <?php endif; ?>
                        <p class="small text-body-secondary mt-3 mb-1">
                            By continuing you agree to our <?= Html::a('licence terms', ['/site/licence']) ?> and <?= Html::a('refund policy', ['/site/refund-policy']) ?>: a full refund until your first download.
                        </p>
                        <p class="small text-body-secondary mb-0">
                            Billing details on the invoice come from <?= Html::a('your profile', ['/account/profile']) ?>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
