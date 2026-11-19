<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Product $model */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$fmt = Yii::$app->formatter;
$url = Url::to(['/store/view', 'slug' => $model->slug], true);
$this->title = ($model->seo_title ?: $model->name . ' (' . $model->typeLabel() . ')') . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $model->seo_description ?: $model->summary;
$this->params['canonical'] = $url;
$this->params['og_type'] = 'product';
$images = array_values(array_filter([$model->cover_image, ...array_map(static fn($i): string => $i->path, $model->images)]));
if ($images !== []) {
    $this->params['og_image'] = Url::to('@web/' . $images[0], true);
}
$whatsapp = preg_replace('/\D/', '', (string) CustomYii::getSetting('whatsapp'));
$service = !$model->hasLicence(); // a service or a care plan: one price, no licence
$licences = $model->isPlan()
    ? ['Monthly' => [$model->price_single_inr, $model->price_single_usd, 'A month at a time. Cancel any time before the next renewal.', 'single']]
    : ($service
    ? ['Fixed price' => [$model->price_single_inr, $model->price_single_usd, 'Done in ' . (int) $model->delivery_days . ' working days from payment and your details.', 'single']]
    : ['Single site' => [$model->price_single_inr, $model->price_single_usd, 'Use on one website.', 'single']]);
if (!$service && $model->hasUnlimited()) {
    $licences['Unlimited sites'] = [$model->price_unlimited_inr, $model->price_unlimited_usd, 'Use on any number of your websites.', 'unlimited'];
}
// For search engines: what it is, its pictures and its prices.
$offers = [];
foreach ($licences as $name => [$inr, $usd]) {
    foreach (['INR' => $inr, 'USD' => $usd] as $currency => $price) {
        $offers[] = ['@type' => 'Offer', 'name' => $service ? $name : $name . ' licence', 'price' => number_format((float) $price, 2, '.', ''), 'priceCurrency' => $currency, 'availability' => 'https://schema.org/InStock', 'url' => $url];
    }
}
$schema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $model->name,
    'description' => $model->summary,
    'image' => array_map(static fn(string $p): string => Url::to('@web/' . $p, true), $images) ?: null,
    'sku' => $model->slug,
    'category' => $model->typeLabel(),
    'brand' => ['@type' => 'Brand', 'name' => $siteName],
    'offers' => $offers,
]);
?>
<script type="application/ld+json"><?= Json::htmlEncode($schema) ?></script>
<script type="application/ld+json"><?= Json::htmlEncode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ...($service ? [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $model->isPlan() ? 'Care plans' : 'Fixed-price services', 'item' => Url::to([$model->isPlan() ? '/store/plans' : '/store/services'], true)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $model->name, 'item' => $url],
        ] : [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Store', 'item' => Url::to(['/store/index'], true)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => \common\models\Product::TECHNOLOGIES[$model->technology] ?? $model->technology, 'item' => Url::to(['/store/index', 'technology' => $model->technology], true)],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $model->name, 'item' => $url],
        ]),
    ],
]) ?></script>
<div class="store-view">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <nav aria-label="breadcrumb" class="mb-3">
                <?php if ($service): ?>
                    <?= $model->isPlan() ? Html::a('Care plans', ['/store/plans'], ['class' => 'mw-crumb']) : Html::a('Fixed-price services', ['/store/services'], ['class' => 'mw-crumb']) ?>
                <?php else: ?>
                    <?= Html::a('Store', ['/store/index'], ['class' => 'mw-crumb']) ?>
                    <span class="mx-2 opacity-50">/</span><?= Html::a(Html::encode(\common\models\Product::TECHNOLOGIES[$model->technology] ?? $model->technology), ['/store/index', 'technology' => $model->technology], ['class' => 'mw-crumb']) ?>
                <?php endif; ?>
            </nav>
            <h1 class="mw-h1 mb-3"><?= Html::encode($model->name) ?></h1>
            <p class="mw-lead mb-3"><?= Html::encode($model->summary) ?></p>
            <p class="small mb-0"><?= Html::encode($model->typeLabel()) ?><?= $model->version ? ' · Version ' . Html::encode($model->version) : '' ?></p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-7">
                    <?php foreach ($images as $i => $path): ?>
                        <?= Html::img('@web/' . $path, ['alt' => $i === 0 ? $model->name : $model->name . ', screenshot ' . $i, 'loading' => $i === 0 ? 'eager' : 'lazy', 'class' => 'img-fluid rounded border mb-4']) ?>
                    <?php endforeach; ?>
                    <h2 class="h4">About it</h2>
                    <?php foreach ($model->paragraphs() as $paragraph): ?>
                        <p><?= nl2br(Html::encode($paragraph)) ?></p>
                    <?php endforeach; ?>
                    <?php if ($model->featureList() !== []): ?>
                        <h2 class="h4 mt-4">Features</h2>
                        <ul class="list-unstyled">
                            <?php foreach ($model->featureList() as $feature): ?>
                                <li class="mb-2"><i class="fas fa-check text-success me-2" aria-hidden="true"></i><?= Html::encode($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ((string) $model->changelog !== ''): ?>
                        <h2 class="h4 mt-4">What's new</h2>
                        <div class="small" style="white-space: pre-line"><?= Html::encode($model->changelog) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5 order-first order-lg-last">
                    <div class="mw-card p-4 mw-sticky-lg">
                        <?php if ($model->demo_url): ?>
                            <?= Html::a('<i class="fas fa-external-link-alt me-2" aria-hidden="true"></i>Try the live demo<span class="visually-hidden"> (opens in a new tab)</span>', $model->demo_url, ['class' => 'btn btn-outline-primary w-100 mb-4', 'target' => '_blank', 'rel' => 'noopener nofollow']) ?>
                        <?php endif; ?>
                        <h2 class="h5"><?= $service ? 'Price' : 'Licences' ?></h2>
                        <?php foreach ($licences as $name => [$inr, $usd, $about, $licence]): ?>
                            <?php $ask = 'Hi, I would like to buy ' . $model->name . ($service ? '.' : ' (' . strtolower($name) . ' licence).'); ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-baseline gap-2">
                                    <h3 class="h6 mb-1"><?= Html::encode($name) ?></h3>
                                    <p class="fw-bold mb-1 text-nowrap">₹<?= $fmt->asDecimal($inr, 0) ?><?= $model->isPlan() ? ' a month' : '' ?></p>
                                </div>
                                <p class="small text-body-secondary mb-2"><?= Html::encode($about) ?> Outside India: US$<?= $fmt->asDecimal($usd, 2) ?><?= $model->isPlan() ? ' a month' : '' ?>.</p>
                                <?= Html::a(($model->isPlan() ? 'Start this plan' : 'Buy now') . '<span class="visually-hidden">: ' . Html::encode($service ? $model->name : strtolower($name) . ' licence') . '</span>', ['/store/buy', 'slug' => $model->slug, 'licence' => $licence], ['class' => 'btn btn-primary btn-sm', 'rel' => 'nofollow']) ?>
                                <?php if ($whatsapp !== ''): ?>
                                    <?= Html::a('<i class="fab fa-whatsapp me-2" aria-hidden="true"></i>Ask on WhatsApp<span class="visually-hidden"> (opens in a new tab)</span>', 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($ask), ['class' => 'btn btn-outline-success btn-sm', 'target' => '_blank', 'rel' => 'noopener']) ?>
                                <?php else: ?>
                                    <?= Html::a('Ask about it', ['/site/contact', 'subject' => 'Buy ' . $model->name . ($service ? '' : ' (' . strtolower($name) . ' licence)')], ['class' => 'btn btn-link btn-sm']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($model->isPlan()): ?>
                        <p class="small mb-2"><i class="fas fa-calendar-check text-primary me-2" aria-hidden="true"></i>Each month is invoiced a week before it starts, and nothing is charged automatically. Once the first month is paid, we open a ticket in your account for access to your website and your requests. <?= Html::a('Refund policy', ['/site/refund-policy']) ?></p>
                        <?php elseif ($service): ?>
                        <p class="small mb-2"><i class="fas fa-clipboard-list text-primary me-2" aria-hidden="true"></i>At checkout you tell us what you need. Once paid, we open a ticket in your account where we talk it through and deliver the work. <?= Html::a('Refund policy', ['/site/refund-policy']) ?></p>
                        <?php else: ?>
                        <p class="small mb-2"><i class="fas fa-tools text-primary me-2" aria-hidden="true"></i>Both licences include our help to set up the <?= Html::encode(strtolower(\common\models\Product::KINDS[$model->kind] ?? 'product')) ?>, 6 months of support, and every new version free. <?= Html::a('Licence terms', ['/site/licence']) ?></p>
                        <?php endif; ?>
                        <p class="small text-body-secondary mb-0">Rupee prices are for buyers in India, US dollar prices for buyers abroad. GST is added where it applies. In India, pay by UPI or bank transfer, or online by card where offered; abroad, pay by PayPal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
