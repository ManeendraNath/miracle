<?php

declare(strict_types=1);

/**
 * Shared frame for the policy pages: heading, last-updated date, body and the
 * business contact block (from Site Settings).
 *
 * @var yii\web\View $this
 * @var string $title
 * @var string $updated
 * @var string $body
 */

use common\components\CustomYii;
use yii\helpers\Html;

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$address = implode(', ', array_filter(array_map(static fn($l): string => trim((string) $l, ' ,'), [
    CustomYii::getSetting('address_line_1'),
    CustomYii::getSetting('address_line_2'),
    CustomYii::getSetting('address_line_3'),
])));
$phone = (string) CustomYii::getSetting('mobile_1');
$email = (string) CustomYii::getSetting('email_1');

$this->title = $title . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $title . ' of ' . $siteName . '.';
?>
<div class="site-legal container py-5">
    <div class="mw-prose">
        <h1 class="fw-bold mb-1"><?= Html::encode($title) ?></h1>
        <p class="text-body-secondary small mb-4">Last updated: <?= Html::encode($updated) ?></p>

        <div class="legal-body lh-lg">
            <?= $body ?>
        </div>

        <div class="card border-0 bg-light mt-5">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-2">Contact us</h2>
                <p class="small mb-0">
                    <?= Html::encode($siteName) ?><?= $address !== '' ? '<br>' . Html::encode($address) : '' ?>
                    <?= $phone !== '' ? '<br>Phone: ' . Html::a(Html::encode(CustomYii::formatPhone($phone)), 'tel:+' . $phone) : '' ?>
                    <?= $email !== '' ? '<br>Email: ' . Html::mailto(Html::encode($email), $email) : '' ?>
                </p>
            </div>
        </div>
    </div>
</div>
