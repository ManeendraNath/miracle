<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Product[] $plans */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$this->title = 'Website care plans | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = 'Monthly website care plans from ' . $siteName . ': updates, backups and fixes for your website, paid month by month and cancelled any time.';
$this->params['canonical'] = Url::to(['/store/plans'], true);
$fmt = Yii::$app->formatter;
?>
<div class="store-plans">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <p class="mw-eyebrow mb-2">Care plans</p>
            <h1 class="mw-h1 mb-3">Website care, month by month</h1>
            <p class="mw-lead mb-0">We keep your website updated, backed up and working. Pay monthly, and cancel any time before the next renewal.</p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <?php if ($plans === []): ?>
                <p class="mw-lead">No care plans are listed yet. <?= Html::a('Ask about website maintenance', ['/site/contact', 'subject' => 'Website maintenance enquiry']) ?> and we will suggest one.</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($plans as $plan): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="mw-card h-100 p-4 d-flex flex-column">
                                <p class="mw-eyebrow mb-1"><?= Html::encode($plan->typeLabel()) ?></p>
                                <h2 class="h4 mb-2"><?= Html::encode($plan->name) ?></h2>
                                <p class="mb-3"><?= Html::encode($plan->summary) ?></p>
                                <p class="mb-3">
                                    <span class="fs-3 fw-bold">₹<?= $fmt->asDecimal($plan->price_single_inr, 0) ?></span> <span class="text-body-secondary">a month</span><br>
                                    <span class="small text-body-secondary">Outside India: US$<?= $fmt->asDecimal($plan->price_single_usd, 2) ?> a month</span>
                                </p>
                                <?php if ($plan->featureList() !== []): ?>
                                    <ul class="list-unstyled small mb-4">
                                        <?php foreach ($plan->featureList() as $feature): ?>
                                            <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= Html::encode($feature) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <div class="mt-auto d-flex flex-wrap gap-2">
                                    <?= Html::a('Start this plan<span class="visually-hidden">: ' . Html::encode($plan->name) . '</span>', ['/store/buy', 'slug' => $plan->slug, 'licence' => 'single'], ['class' => 'btn btn-primary', 'rel' => 'nofollow']) ?>
                                    <?= Html::a('Details<span class="visually-hidden">: ' . Html::encode($plan->name) . '</span>', ['/store/view', 'slug' => $plan->slug], ['class' => 'btn btn-outline-primary']) ?>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="small text-body-secondary mt-4">
                    <p class="mb-1">Rupee prices are for buyers in India (GST added where it applies); US dollar prices for buyers abroad. Each month is invoiced a week before it starts: nothing is charged automatically.</p>
                    <p class="mb-0">Outside India, later months are invoiced in rupees and paid by PayPal in US dollars: about the same dollar amount, moving a little with the exchange rate. Work beyond the plan is quoted first. <?= Html::a('Refund policy', ['/site/refund-policy']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
