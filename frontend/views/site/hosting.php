<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Hosting, Domains and Website Maintenance | Miracle Web Technologies';
$this->params['bare'] = true;
$this->params['meta_description'] = 'cPanel hosting, domain registration and website maintenance, all in one customer portal. Renewals are invoiced first and never charged automatically.';

$plans = [
    [
        'name' => 'Starter',
        'for' => 'A single small website, a staging copy or a lightweight PHP site.',
        'price' => '₹499',
        'specs' => [
            'NVMe storage' => '10 GB',
            'Websites' => '1',
            'Email inboxes' => '5',
            'MySQL databases' => '1',
            'Backups' => 'Weekly',
            'Malware scanning' => 'Not included',
            'Staging site' => 'Not included',
        ],
    ],
    [
        'name' => 'Business',
        'for' => 'Busy business websites, online shops and customer portals.',
        'price' => '₹1,499',
        'specs' => [
            'NVMe storage' => '50 GB',
            'Websites' => '5',
            'Email inboxes' => '25',
            'MySQL databases' => '5',
            'Backups' => 'Daily',
            'Malware scanning' => 'Included',
            'Staging site' => 'Included',
        ],
        'featured' => true,
    ],
    [
        'name' => 'Agency',
        'for' => 'Agencies and larger clients running many websites.',
        'price' => '₹3,499',
        'specs' => [
            'NVMe storage' => '150 GB',
            'Websites' => 'Unlimited',
            'Email inboxes' => '100',
            'MySQL databases' => 'Unlimited',
            'Backups' => 'Daily',
            'Malware scanning' => 'Included',
            'Staging site' => 'Included',
        ],
    ],
];

$questions = [
    'Can I pay from outside India?' => 'Yes. You can pay online by international card through Razorpay. Prices are in Indian Rupees, so your card issuer converts the amount and may add a fee. If your card is declined, raise a support ticket and we will help.',
    'Will you charge my card automatically?' => 'No. Renewals are invoiced first and you choose when to pay.',
    'Do these prices include tax?' => 'We are not registered under GST at present, so none is added. If we register, GST will be added at the applicable rate where it applies and shown on your invoice.',
    'Do renewal prices change?' => 'Renewal prices include an increase at the rate agreed for the service: every year for domains, every two years for hosting and every three years for maintenance. The renewal invoice always shows the price that applies.',
    'Whose name is my domain registered in?' => 'Yours. Domains are registered in your name through accredited registrars.',
    'What if I do not pay a renewal on time?' => 'A service may be suspended if its invoice stays unpaid after the due date. A domain that is not renewed in time may expire, and could be lost or carry a registry recovery fee.',
    'How do I cancel?' => 'Raise a support ticket in the customer portal or email us before the next renewal date. The service is not renewed, and you keep what you have paid for until the end of that period.',
];
?>
<div class="site-hosting">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <h1 class="mw-h1 mb-3">Hosting, domains and maintenance</h1>
            <p class="mw-lead mb-0">Keep your site running with one team, whether we built it or not. Everything appears in one customer portal.</p>
        </div>
    </div>

    <section id="hosting-tiers" class="py-5">
        <div class="container">
            <h2 class="mw-h2">Hosting plans</h2>
            <p class="mw-lead mt-3 mb-4">Every plan runs on cPanel, with free SSL certificates, firewall protection and your customer portal. Pay monthly, yearly or for several years.</p>
            <div class="row g-4">
                <?php foreach ($plans as $plan): ?>
                    <div class="col-lg-4">
                        <div class="mw-plan<?= !empty($plan['featured']) ? ' mw-plan-featured' : '' ?>">
                            <?php if (!empty($plan['featured'])): ?>
                                <span class="mw-plan-tag">Recommended</span>
                            <?php endif; ?>
                            <h3><?= Html::encode($plan['name']) ?></h3>
                            <p class="mw-plan-for"><?= Html::encode($plan['for']) ?></p>
                            <p class="mw-plan-price"><strong><?= Html::encode($plan['price']) ?></strong> / month</p>
                            <dl class="mw-plan-specs">
                                <?php foreach ($plan['specs'] as $label => $value): ?>
                                    <div><dt><?= Html::encode($label) ?></dt><dd><?= Html::encode($value) ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                            <?= Html::a('Ask about ' . Html::encode($plan['name']), ['site/contact', 'subject' => 'Hosting enquiry: ' . $plan['name']], ['class' => 'btn w-100 ' . (!empty($plan['featured']) ? 'btn-primary' : 'btn-outline-primary')]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mw-plan-notes mw-prose">
                <p>Prices are in Indian Rupees (INR). Hosting renewal prices include an increase every two years at the rate agreed for your service, and each renewal invoice shows the price that applies.</p>
                <p>We take backups as a precaution and don't guarantee them, so please keep your own copy of your site.</p>
                <p class="mb-0">Cancel within 7 days of first activating a new hosting plan for a full refund, less the fee for any domain supplied with it. See the <?= Html::a('refund policy', ['site/refund-policy']) ?>.</p>
            </div>
        </div>
    </section>

    <section class="mw-section mw-section-soft">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mw-panel h-100">
                        <h2 class="h4 fw-bold">Domain registration</h2>
                        <p class="text-body-secondary">New domains and on-time renewals, registered in your name through accredited registrars.</p>
                        <?= Html::a('Ask about a domain', ['site/contact', 'subject' => 'Domain enquiry'], ['class' => 'fw-semibold']) ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mw-panel h-100">
                        <h2 class="h4 fw-bold">Website maintenance</h2>
                        <p class="text-body-secondary">Updates, fixes and support under an annual maintenance contract.</p>
                        <?= Html::a('Ask about maintenance', ['site/contact', 'subject' => 'Website maintenance enquiry'], ['class' => 'fw-semibold']) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mw-section">
        <div class="container">
            <div class="mw-faq mw-prose">
                <h2 class="mw-h2 mb-4">Before you decide</h2>
                <?php foreach ($questions as $question => $answer): ?>
                    <details>
                        <summary><?= Html::encode($question) ?></summary>
                        <p><?= Html::encode($answer) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
