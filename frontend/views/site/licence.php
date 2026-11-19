<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use common\models\Product;
use yii\helpers\Html;

$name = Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$months = Product::SUPPORT_MONTHS;
$refund = Html::encode(\yii\helpers\Url::to(['/site/refund-policy']));

echo $this->render('_legal', ['title' => 'Licence Terms for Themes, Plugins & Software', 'updated' => '19 Nov 2026', 'body' => <<<HTML
<p>These terms apply to the website themes and plugins (HTML, WordPress and Yii2) you buy from the {$name} store. Paying for a product means you accept them.</p>

<h2 class="h5 fw-bold mt-4">1. What each licence allows</h2>
<ul>
    <li><strong>Single site:</strong> use the product on one website (one domain). A copy on a staging or development address for that same website is included.</li>
    <li><strong>Unlimited sites:</strong> use the product on any number of your websites.</li>
</ul>
<p>Either way you may change the product as much as you like for your websites. You may not sell, share or give away the product files, or offer them for download, on their own or as part of another product.</p>

<h2 class="h5 fw-bold mt-4">2. Licence key and download</h2>
<p>Once your payment is confirmed, your licence key and download appear on <strong>My Purchases</strong> in your account. A payment by UPI or bank transfer is confirmed once our team has checked the proof you upload.</p>

<h2 class="h5 fw-bold mt-4">3. Updates</h2>
<p>Every new version of a product you bought is free. Download it from My Purchases whenever it is released; we usually email you when a new version is out.</p>

<h2 class="h5 fw-bold mt-4">4. Setup help and support</h2>
<p>For {$months} months from your payment we help you set the product up on your website and answer questions about it, and we fix faults in the product itself. Support does not cover changes or new features made for you, third-party plugins or hosting problems; we can quote for that work separately. Ask for help through a support ticket in your account.</p>

<h2 class="h5 fw-bold mt-4">5. Refunds</h2>
<p>You get a full refund if you ask before your first download. After you have downloaded the product, a refund is given only if it does not work as described and we cannot fix it. A refund ends the licence: the licence key and download stop working, and you must stop using the product. See our <a href="{$refund}">Refund &amp; Cancellation Policy</a>.</p>

<h2 class="h5 fw-bold mt-4">6. Ownership</h2>
<p>{$name}, or the authors named in the product, keep the copyright in the product. Your licence lets you use it as described above.</p>

<h2 class="h5 fw-bold mt-4">7. Software sold by subscription</h2>
<p>Some software, such as our billing portal, is sold by the month or by the year instead of with a one-time licence. For it, sections 1, 3 and 4 above are replaced by these terms:</p>
<ul>
    <li>A subscription is for one installation, at the web address given when you bought it. To move it to another address, ask us in a support ticket. A test or staging copy may run without the key; it then shows a reminder in its admin panel.</li>
    <li>While the subscription is paid you get every new version and our support. Each next month or year is invoiced before it starts; nothing is charged automatically.</li>
    <li>Your installation checks its licence key with us once a day. It sends only the key and its web address.</li>
    <li>You can cancel any time before the next renewal. If a subscription ends, your installation keeps working with the version you have, but downloads, updates and support stop until it is renewed.</li>
    <li>Refunds follow section 5 for the first period. A renewal already paid is not refunded once its period has started.</li>
</ul>
HTML]);
