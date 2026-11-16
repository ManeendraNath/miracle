<?php

declare(strict_types=1);

/**
 * The dark panel beside the login, signup and password forms (hidden on phones, where the form comes first).
 *
 * @var yii\web\View $this
 * @var string $title
 * @var string $text
 * @var bool $portal true: list what the customer portal is for (login and signup)
 */

use common\components\CustomYii;
use yii\bootstrap5\Html;

$phone = CustomYii::getSetting('mobile_1');
$email = CustomYii::getSetting('email_1');
?>
<div class="col-md-5 d-none d-md-flex auth-panel mw-glow">
    <div class="d-flex flex-column justify-content-between p-4 p-lg-5 w-100 position-relative z-1">
        <div>
            <p class="mw-eyebrow mb-3">Customer portal</p>
            <p class="auth-panel-title mb-3"><?= Html::encode($title) ?></p>
            <p class="mb-4"><?= Html::encode($text) ?></p>
            <?php if (!empty($portal)): ?>
                <ul class="list-unstyled auth-panel-list mb-0">
                    <li><i class="fas fa-check" aria-hidden="true"></i>Services and renewal dates</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i>Invoices, online payments and receipts</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i>Support requests and replies</li>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ($phone || $email): ?>
            <p class="small mb-0 mt-4 auth-panel-help">
                Need help?
                <?php if ($phone): ?>
                    <a href="tel:+<?= Html::encode($phone) ?>"><?= Html::encode(CustomYii::formatPhone((string) $phone)) ?></a>
                <?php endif; ?>
                <?php if ($phone && $email): ?>
                    <span aria-hidden="true">&middot;</span>
                <?php endif; ?>
                <?php if ($email): ?>
                    <a href="mailto:<?= Html::encode($email) ?>"><?= Html::encode($email) ?></a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
