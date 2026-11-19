<?php

declare(strict_types=1);

use common\models\Product;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var Product $model */
/** @var string $licence */
/** @var bool $usd buyer abroad: priced in US dollars, no GST */
/** @var bool $canPay false: a buyer abroad while PayPal is off */
/** @var string $brief what a service buyer typed */
/** @var string $website */
/** @var string|null $error why the order was not placed */

$fmt = Yii::$app->formatter;
$price = (float) $model->price($licence, $usd);
$service = $model->isService();
$plan = $model->isPlan();
$work = $service || $plan; // no licence: the brief and website are asked for
$software = $model->isSoftware();
$period = Product::periodName($licence);
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
                            <p class="fw-bold fs-5 mb-0 text-nowrap"><?= $usd ? 'US$' . $fmt->asDecimal($price, 2) : '₹' . $fmt->asDecimal($price, 0) ?><?= $plan ? ' <span class="fs-6 fw-normal text-body-secondary">first month</span>' : ($software ? ' <span class="fs-6 fw-normal text-body-secondary">first ' . $period . '</span>' : '') ?></p>
                        </div>
                        <?php if ($software): ?>
                            <p class="text-body-secondary mb-3"><?= Html::encode($model->typeLabel()) ?>, <?= Html::encode(strtolower($model->licenceName($licence))) ?> subscription<?= $model->version ? ', version ' . Html::encode($model->version) : '' ?></p>
                        <?php elseif ($plan): ?>
                            <p class="text-body-secondary mb-3"><?= Html::encode($model->typeLabel()) ?>, monthly</p>
                        <?php elseif ($service): ?>
                            <p class="text-body-secondary mb-3"><?= Html::encode($model->typeLabel()) ?>, fixed price, done in <?= (int) $model->delivery_days ?> working days</p>
                        <?php else: ?>
                            <p class="text-body-secondary mb-3">
                                <?= Html::encode($model->typeLabel()) ?>, <?= Html::encode(strtolower($model->licenceName($licence))) ?> licence<?= $model->version ? ', version ' . Html::encode($model->version) : '' ?>
                            </p>
                        <?php endif; ?>
                        <ul class="list-unstyled small mb-4">
                            <?php if ($plan): ?>
                                <?php foreach ($model->featureList() as $feature): ?>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= Html::encode($feature) ?></li>
                                <?php endforeach; ?>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>Each next month is invoiced a week before it starts; nothing is charged automatically. Cancel any time before the next renewal.</li>
                            <?php elseif ($software): ?>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>For one installation, at the address you give below.</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>Every new version and our support while the subscription is paid.</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>Each next <?= $period ?> is invoiced before it starts; nothing is charged automatically. Cancel any time before the next renewal: your copy keeps working, without updates or support.</li>
                            <?php elseif ($service): ?>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>We start once the payment is confirmed, and finish within <?= (int) $model->delivery_days ?> working days of it (and of anything we need from you).</li>
                            <?php else: ?>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= $licence === 'single' ? 'Use on one website.' : 'Use on any number of your websites.' ?></li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>Our help to set it up, 6 months of support, and every new version free.</li>
                            <?php endif; ?>
                            <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= $usd ? 'Priced in US dollars for buyers outside India. No GST.' : 'GST is added on the invoice where it applies.' ?></li>
                        </ul>
                        <h3 class="h6">What happens next</h3>
                        <ol class="small mb-4 ps-3">
                            <li>We create your invoice and take you to it.</li>
                            <li><?= $usd ? 'Pay online by PayPal, with a card or a PayPal account.' : 'Pay by UPI or bank transfer and upload the payment proof, or pay online by card where offered.' ?></li>
                            <?php if ($plan): ?>
                                <li>Once the payment is confirmed, the plan starts and we open a ticket where you send us access to your website: both are on <strong>My Purchases</strong>.</li>
                            <?php elseif ($service): ?>
                                <li>Once the payment is confirmed, we open a ticket for the job: follow it, and talk to us, on <strong>My Purchases</strong>.</li>
                            <?php else: ?>
                                <li>Once the payment is confirmed, your licence key and download appear on <strong>My Purchases</strong>.</li>
                            <?php endif; ?>
                        </ol>
                        <?php if ($canPay): ?>
                            <?= Html::beginForm() ?>
                                <?php if ($error !== null): ?>
                                    <div class="alert alert-danger" role="alert"><?= Html::encode($error) ?></div>
                                <?php endif; ?>
                                <?php if ($software): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="website">Where you will install it</label>
                                        <?= Html::textInput('website', $website, ['id' => 'website', 'class' => 'form-control', 'type' => 'text', 'inputmode' => 'url', 'maxlength' => 255, 'required' => true, 'placeholder' => 'https://billing.example.com', 'aria-describedby' => 'website-help']) ?>
                                        <div class="form-text" id="website-help">The web address of your copy: the licence works there. To move it later, ask us in a support ticket.</div>
                                    </div>
                                <?php elseif ($plan): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="website">Website address</label>
                                        <?= Html::textInput('website', $website, ['id' => 'website', 'class' => 'form-control', 'type' => 'text', 'inputmode' => 'url', 'maxlength' => 255, 'required' => true, 'placeholder' => 'https://example.com']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="brief">Anything we should know <span class="text-body-secondary">(optional)</span></label>
                                        <?= Html::textarea('brief', $brief, ['id' => 'brief', 'class' => 'form-control', 'rows' => 3, 'maxlength' => 5000, 'aria-describedby' => 'brief-help']) ?>
                                        <div class="form-text" id="brief-help">For example, where it is hosted, or what worries you about it.</div>
                                    </div>
                                <?php elseif ($service): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="brief">What do you need done?</label>
                                        <?= Html::textarea('brief', $brief, ['id' => 'brief', 'class' => 'form-control', 'rows' => 5, 'required' => true, 'minlength' => 20, 'maxlength' => 5000, 'aria-describedby' => 'brief-help']) ?>
                                        <div class="form-text" id="brief-help">The problem or the change, and anything we should know. We may ask for access to your website or hosting in the ticket.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="website">Website address <span class="text-body-secondary">(optional)</span></label>
                                        <?= Html::textInput('website', $website, ['id' => 'website', 'class' => 'form-control', 'type' => 'text', 'inputmode' => 'url', 'maxlength' => 255, 'placeholder' => 'https://example.com']) ?>
                                    </div>
                                <?php endif; ?>
                                <?= Html::submitButton('Continue to payment', ['class' => 'btn btn-primary w-100']) ?>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0" role="status">
                                Online payment from outside India is not available right now.
                                <?= Html::a('Contact us', ['/site/contact', 'subject' => 'Buy ' . $model->name . ($work ? '' : ' (' . strtolower($model->licenceName($licence)) . ($software ? ' subscription)' : ' licence)'))]) ?> and we will arrange your payment.
                            </div>
                        <?php endif; ?>
                        <p class="small text-body-secondary mt-3 mb-1">
                            <?php if ($plan): ?>
                                By continuing you agree to our <?= Html::a('refund policy', ['/site/refund-policy']) ?>: a full refund for a month in which no work has been done.
                            <?php elseif ($service): ?>
                                By continuing you agree to our <?= Html::a('refund policy', ['/site/refund-policy']) ?>: a full refund until we start the work.
                            <?php else: ?>
                                By continuing you agree to our <?= Html::a('licence terms', ['/site/licence']) ?> and <?= Html::a('refund policy', ['/site/refund-policy']) ?>: a full refund until your first download.
                            <?php endif; ?>
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
