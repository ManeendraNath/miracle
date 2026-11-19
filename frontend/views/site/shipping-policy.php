<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$refund = Url::to(['/site/refund-policy']);

echo $this->render('_legal', ['title' => 'Shipping & Delivery Policy', 'updated' => '19 Nov 2026', 'body' => <<<HTML
<p>{$name} sells digital services only. Nothing is shipped physically and there are no shipping charges. Services are delivered online as follows.</p>

<h2 class="h5 fw-bold mt-4">Delivery times</h2>
<ul>
    <li><strong>Domain registration and renewal:</strong> processed within 1 working day of payment. Registry updates can take up to 48 hours to apply worldwide.</li>
    <li><strong>Web hosting:</strong> activated within 1 working day of payment. We send your login details to your registered email address.</li>
    <li><strong>Maintenance (AMC):</strong> covers the period shown on the invoice (a renewal continues from the day after the previous period ends); requests are handled through support tickets during the paid period.</li>
    <li><strong>Website themes and plugins from our store:</strong> the download and licence key appear on My Purchases in your account as soon as the payment is confirmed: at once for online payments, and within 1 working day for UPI / bank transfers, once we have checked the payment proof.</li>
    <li><strong>Fixed-price services:</strong> done within the working days shown for the service, counted from the payment and from receiving what we need from you (such as access to your website). We open a ticket for the job in your account, and deliver the work there.</li>
    <li><strong>Shopping carts, web applications, websites and upgrades:</strong> delivered on the timeline agreed in the quotation, once content and approvals are received from you.</li>
</ul>

<h2 class="h5 fw-bold mt-4">Confirmation</h2>
<p>Every payment is confirmed by email and shown in the customer portal (UPI / bank transfers once we have verified the payment proof), where you can also see the status and expiry date of each service.</p>

<h2 class="h5 fw-bold mt-4">Delays</h2>
<p>If a service is not delivered within the time above, contact us and we will either complete it promptly or refund you as per our <a href="{$refund}">Refund &amp; Cancellation Policy</a>.</p>
HTML]);
