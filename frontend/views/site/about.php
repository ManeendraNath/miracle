<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$this->title = 'About Us | ' . $siteName;
$this->params['breadcrumbs'][] = 'About Us';
$this->params['meta_description'] = $siteName . ': website design and development, domain registration, web hosting and website maintenance from Delhi.';
?>
<div class="site-about container py-5">
    <div class="mx-auto" style="max-width: 860px;">
        <h1 class="fw-bold mb-3">About Us</h1>
        <p class="lead"><?= Html::encode($siteName) ?> is a Delhi-based web services company helping businesses get online and stay online.</p>

        <h2 class="h5 fw-bold mt-4">What we do</h2>
        <ul class="lh-lg">
            <li><strong>Website design and development</strong>: business websites built to your brief.</li>
            <li><strong>Domain registration</strong>: new domains and on-time renewals.</li>
            <li><strong>Web hosting</strong>: managed hosting plans with email.</li>
            <li><strong>Website maintenance (AMC)</strong>: updates, fixes and support under an annual contract.</li>
        </ul>

        <h2 class="h5 fw-bold mt-4">Working with us</h2>
        <p>Every customer gets a portal to see their services and expiry dates, pay invoices online or by UPI / bank transfer, download receipts and raise support tickets.</p>

        <p class="mt-4">
            <?= Html::a('Contact us', ['/site/contact'], ['class' => 'btn btn-primary me-2']) ?>
            <?= Html::a('View hosting plans', ['/site/index', '#' => 'hosting-tiers'], ['class' => 'btn btn-outline-primary']) ?>
        </p>
    </div>
</div>
