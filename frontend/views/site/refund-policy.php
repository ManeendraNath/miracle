<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use yii\helpers\Html;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');

echo $this->render('_legal', ['title' => 'Refund & Cancellation Policy', 'updated' => '03 Nov 2026', 'body' => <<<HTML
<p>This policy explains how you can cancel a service bought from {$name} and when a refund is given.</p>

<h2 class="h5 fw-bold mt-4">1. Cancelling a service</h2>
<p>You can cancel any recurring service (domain, hosting or maintenance) by raising a support ticket in the customer portal or emailing us before its next renewal date. The service then stays active until the end of the period already paid for and is not renewed.</p>

<h2 class="h5 fw-bold mt-4">2. Refunds</h2>
<ul>
    <li><strong>Domain registration and renewal:</strong> never refundable, because the registry fee cannot be recovered. This includes a domain supplied as part of a hosting plan.</li>
    <li><strong>Web hosting:</strong> a full refund if you cancel within 7 days of first activation of a new hosting plan, less the fee for any domain included with it. After that, or for renewals, no refund is given for the current period.</li>
    <li><strong>Maintenance (AMC):</strong> a full refund if you cancel before any work has been done in the paid period. Once work has started, no refund is given for that period.</li>
    <li><strong>Website design and development:</strong> an advance is refundable in full if you cancel before work begins. After work begins, the amount for work already completed is kept and any balance is refunded.</li>
    <li><strong>Duplicate or excess payments:</strong> always refunded in full, or adjusted against your next invoice if you prefer.</li>
</ul>

<h2 class="h5 fw-bold mt-4">3. How refunds are paid</h2>
<p>Approved refunds are made to the original payment method: payments made through Razorpay are refunded through Razorpay, and UPI / bank transfers are refunded to the account they came from. We process an approved refund within 5–7 working days; your bank or card issuer may take a few more days to show it.</p>

<h2 class="h5 fw-bold mt-4">4. Failed payments</h2>
<p>If money was debited but the payment failed or the invoice still shows as unpaid, it is usually reversed automatically by your bank within 5–7 working days. If not, contact us with the payment reference and we will resolve it.</p>

<h2 class="h5 fw-bold mt-4">5. Payment disputes</h2>
<p>Please contact us before raising a dispute or chargeback with your bank; most issues are resolved faster directly. A service may be suspended while a chargeback on its invoice is open.</p>

<h2 class="h5 fw-bold mt-4">6. How to request a refund</h2>
<p>Raise a support ticket in the customer portal or email us with your invoice number and the reason. We reply within 2 working days.</p>
HTML]);
