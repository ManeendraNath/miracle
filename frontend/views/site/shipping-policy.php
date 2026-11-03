<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');

echo $this->render('_legal', ['title' => 'Shipping & Delivery Policy', 'updated' => '03 Nov 2026', 'body' => <<<HTML
<p>{$name} sells digital services only. Nothing is shipped physically and there are no shipping charges. Services are delivered online as follows.</p>

<h2 class="h5 fw-bold mt-4">Delivery times</h2>
<ul>
    <li><strong>Domain registration and renewal:</strong> processed within 24 hours of payment (working days). Registry updates can take up to 48 hours to apply worldwide.</li>
    <li><strong>Web hosting:</strong> activated within 24 hours of payment (working days). Login details are sent to your registered email address.</li>
    <li><strong>Maintenance (AMC):</strong> starts from the date on the invoice; requests are handled through support tickets during the paid period.</li>
    <li><strong>Website design and development:</strong> delivered on the timeline agreed in the quotation, once content and approvals are received from you.</li>
</ul>

<h2 class="h5 fw-bold mt-4">Confirmation</h2>
<p>Every payment is confirmed by email and shown in the customer portal, where you can also see the status and expiry date of each service.</p>

<h2 class="h5 fw-bold mt-4">Delays</h2>
<p>If a service is not delivered within the time above, contact us and we will either complete it promptly or refund you as per our Refund &amp; Cancellation Policy.</p>
HTML]);
