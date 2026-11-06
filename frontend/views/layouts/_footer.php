<?php

use common\components\CustomYii;
use yii\bootstrap5\Html;

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$address = array_filter(array_map('trim', [
    (string) CustomYii::getSetting('address_line_1'),
    (string) CustomYii::getSetting('address_line_2'),
    (string) CustomYii::getSetting('address_line_3'),
]), static fn(string $line): bool => trim($line, ' ,') !== '');
$phones = array_filter([CustomYii::getSetting('mobile_1'), CustomYii::getSetting('mobile_2')]);
$emails = array_filter([CustomYii::getSetting('email_1'), CustomYii::getSetting('email_2')]);
$socials = array_filter([
    'Facebook' => ['fab fa-facebook-f', CustomYii::getSetting('facebook')],
    'X' => ['fab fa-twitter', CustomYii::getSetting('x')],
    'LinkedIn' => ['fab fa-linkedin-in', CustomYii::getSetting('linkedin')],
    'Instagram' => ['fab fa-instagram', CustomYii::getSetting('instagram')],
    'YouTube' => ['fab fa-youtube', CustomYii::getSetting('youtube')],
], static fn(array $s): bool => !empty($s[1]));
?>
<footer class="site-footer footer mt-auto pt-5 pb-4 text-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center mb-3">
                    <?= Html::img('@web/images/logo.svg', ['alt' => '', 'width' => 54, 'height' => 40, 'class' => 'me-2']) ?>
                    <h2 class="h5 fw-bold m-0"><?= Html::encode($siteName) ?></h2>
                </div>
                <p class="small footer-muted mb-0">
                    Shopping carts, web applications and websites, built to your brief. Based in Delhi, India.
                </p>
            </div>

            <div class="col-sm-6 col-lg-4">
                <h2 class="h6 fw-bold footer-heading mb-3">Contact</h2>
                <ul class="list-unstyled small mb-0">
                    <?php foreach ($phones as $phone): ?>
                        <li class="mb-2">
                            <i class="fas fa-phone-alt fa-fw me-2 footer-muted" aria-hidden="true"></i>
                            <a href="tel:+<?= Html::encode($phone) ?>"><?= Html::encode(CustomYii::formatPhone((string) $phone)) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($emails as $email): ?>
                        <li class="mb-2">
                            <i class="fas fa-envelope fa-fw me-2 footer-muted" aria-hidden="true"></i>
                            <a href="mailto:<?= Html::encode($email) ?>"><?= Html::encode($email) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($address): ?>
                        <li class="d-flex">
                            <i class="fas fa-map-marker-alt fa-fw me-2 mt-1 footer-muted" aria-hidden="true"></i>
                            <span class="footer-muted"><?= implode('<br>', array_map(static fn(string $l): string => Html::encode(rtrim($l, ', ')), $address)) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-sm-6 col-lg-3">
                <h2 class="h6 fw-bold footer-heading mb-3">Quick links</h2>
                <ul class="list-unstyled small mb-3">
                    <li class="mb-2"><?= Html::a('What we build', ['/site/index', '#' => 'services']) ?></li>
                    <li class="mb-2"><?= Html::a('Our work', ['/site/index', '#' => 'work']) ?></li>
                    <li class="mb-2"><?= Html::a('Website upgrades', ['/site/audit']) ?></li>
                    <li class="mb-2"><?= Html::a('Hosting and domains', ['/site/hosting']) ?></li>
                    <li class="mb-2"><?= Html::a('About us', ['/site/about']) ?></li>
                    <li class="mb-2"><?= Html::a('Contact us', ['/site/contact']) ?></li>
                    <li class="mb-2"><?= Yii::$app->user->isGuest ? Html::a('Customer login', ['/site/login']) : Html::a('My account', ['/dashboard/index']) ?></li>
                </ul>
                <?php if ($socials): ?>
                    <div class="d-flex gap-2">
                        <?php foreach ($socials as $label => [$icon, $url]): ?>
                            <a href="<?= Html::encode($url) ?>" target="_blank" rel="noopener" class="footer-social" title="<?= $label ?>" aria-label="<?= $label ?>">
                                <i class="<?= $icon ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-4 footer-rule">
        <div class="d-flex flex-wrap justify-content-between gap-2 small footer-muted">
            <div>&copy; <?= date('Y') ?> <?= Html::encode($siteName) ?>. All rights reserved.</div>
            <div class="d-flex flex-wrap gap-3">
                <?= Html::a('Terms &amp; Conditions', ['/site/terms']) ?>
                <?= Html::a('Privacy Policy', ['/site/privacy']) ?>
                <?= Html::a('Refund &amp; Cancellation', ['/site/refund-policy']) ?>
                <?= Html::a('Shipping &amp; Delivery', ['/site/shipping-policy']) ?>
            </div>
        </div>
    </div>
</footer>
