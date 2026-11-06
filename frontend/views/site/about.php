<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$this->title = 'About Us | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $siteName . ' is a web development company based in Delhi, India, building shopping carts, web applications and websites for clients in India and abroad.';
?>
<div class="site-about">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <h1 class="mw-h1 mb-3">About Us</h1>
            <p class="mw-lead mb-0"><?= Html::encode($siteName) ?> is a web development company based in Delhi, India. We build shopping carts, web applications and websites, for clients in India and abroad. The company is owned and operated by Nidhi Trivedi.</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <h2 class="mw-h2 mb-3">What we do</h2>
                <ul class="lh-lg">
                    <li><strong>Shopping carts and online stores:</strong> catalogues, checkout, payment gateway integration and order management.</li>
                    <li><strong>Web applications:</strong> customer portals, billing systems, dashboards and internal tools.</li>
                    <li><strong>Business websites:</strong> built to your brief.</li>
                    <li><strong>Upgrades:</strong> improving the site you already have, or bringing an older application up to date.</li>
                    <li><strong>Hosting, domains and maintenance:</strong> keeping your site online and up to date after launch.</li>
                </ul>
                <p class="mb-0">We work with PHP, Yii, MySQL, PostgreSQL and Docker.</p>
            </div>
            <div class="col-lg-5">
                <div class="mw-panel">
                    <h2 class="h4 fw-bold">Working with us</h2>
                    <p>You get a written quote before work starts, and the finished work is yours once its invoices are paid in full.</p>
                    <p>Every customer gets a portal to see their services and invoices, download receipts and raise support tickets.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?= Html::a('Get a project quote', ['/site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('See how a project works', ['/site/index', '#' => 'process'], ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
