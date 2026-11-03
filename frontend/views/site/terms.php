<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$refund = Url::to(['/site/refund-policy']);
$privacy = Url::to(['/site/privacy']);

echo $this->render('_legal', ['title' => 'Terms & Conditions', 'updated' => '03 Nov 2026', 'body' => <<<HTML
<p>These terms apply to every service bought from {$name} ("we", "us") through this website, the customer portal, an invoice or a quotation. By placing an order or paying an invoice you accept them.</p>

<h2 class="h5 fw-bold mt-4">1. Services</h2>
<p>We provide website design and development, domain name registration and renewal, web hosting, and website maintenance (annual maintenance contracts). The scope, price and billing cycle of each service are as shown on the quotation, order or invoice.</p>

<h2 class="h5 fw-bold mt-4">2. Accounts</h2>
<p>You are responsible for keeping your portal login private and for all activity under it. Please give accurate contact and billing details and keep them up to date.</p>

<h2 class="h5 fw-bold mt-4">3. Prices, invoices and payment</h2>
<ul>
    <li>All prices are in Indian Rupees (INR). GST is not charged while we are not registered under GST; if we register, GST will be added at the applicable rate and shown on the invoice.</li>
    <li>Invoices are payable by the due date shown. Payments can be made online through Razorpay (cards, UPI, net banking, wallets) or by UPI / bank transfer with the payment proof uploaded in the portal.</li>
    <li>Recurring services (domains, hosting, maintenance) are invoiced before each renewal. A service may be suspended if its invoice remains unpaid after the due date, and a domain that is not renewed in time may expire and be lost or carry a registry recovery fee.</li>
</ul>

<h2 class="h5 fw-bold mt-4">4. Domains</h2>
<p>Domain names are registered in your name through accredited registrars and are subject to the registry's rules. We are not responsible for a name becoming unavailable before payment is received.</p>

<h2 class="h5 fw-bold mt-4">5. Hosting and acceptable use</h2>
<p>You must not use our hosting for unlawful content, spam, malware, phishing or anything that harms our servers or other customers. We may suspend a service that breaches this, with notice where possible. You are responsible for your own content and should keep your own copy of it; we take backups as a precaution but do not guarantee them.</p>

<h2 class="h5 fw-bold mt-4">6. Website projects</h2>
<p>Project timelines depend on you supplying content, approvals and feedback on time. Ownership of the finished website passes to you once its invoices are paid in full. We may show completed work in our portfolio unless you ask us not to.</p>

<h2 class="h5 fw-bold mt-4">7. Cancellations and refunds</h2>
<p>See our <a href="{$refund}">Refund &amp; Cancellation Policy</a>.</p>

<h2 class="h5 fw-bold mt-4">8. Privacy</h2>
<p>How we handle your data is described in our <a href="{$privacy}">Privacy Policy</a>.</p>

<h2 class="h5 fw-bold mt-4">9. Liability</h2>
<p>We do our best to keep services available but do not promise uninterrupted service. Our total liability for any claim is limited to the amount you paid for the service concerned in the twelve months before the claim. We are not liable for indirect losses such as lost profit or data, or for outages caused by third parties (registries, data centres, payment providers) or events outside our control.</p>

<h2 class="h5 fw-bold mt-4">10. Changes</h2>
<p>We may update these terms. The version on this page applies from its "last updated" date; changes do not affect invoices already paid.</p>

<h2 class="h5 fw-bold mt-4">11. Governing law</h2>
<p>These terms are governed by the laws of India. Courts at Delhi have exclusive jurisdiction.</p>
HTML]);
