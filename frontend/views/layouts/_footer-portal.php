<?php

declare(strict_types=1);

use common\components\CustomYii;
use common\components\PortalOnly;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */

// A portal-only copy: the business's name and its own policy pages (Site Settings), nothing of ours.
$founded = (int) CustomYii::getSetting('founded_year');
$links = array_filter([
    'Terms &amp; Conditions' => PortalOnly::policyUrl('terms'),
    'Privacy Policy' => PortalOnly::policyUrl('privacy'),
    'Refund &amp; Cancellation' => PortalOnly::policyUrl('refund'),
]);
?>
<footer class="site-footer footer mt-auto py-4 text-white">
    <div class="container d-flex flex-wrap justify-content-between gap-2 small footer-muted">
        <div>&copy; <?= $founded > 0 && $founded < (int) date('Y') ? $founded . '-' : '' ?><?= date('Y') ?> <?= Html::encode(CustomYii::businessName()) ?>. All rights reserved.</div>
        <div class="d-flex flex-wrap gap-3">
            <?php if (CustomYii::getSetting('site_url')): ?>
                <?= Html::a('Our website', CustomYii::businessUrl()) ?>
            <?php endif; ?>
            <?php foreach ($links as $label => $url): ?>
                <?= Html::a($label, $url, ['target' => '_blank', 'rel' => 'noopener']) ?>
            <?php endforeach; ?>
        </div>
    </div>
</footer>
