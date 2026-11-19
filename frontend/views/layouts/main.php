<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico" sizes="any" />
</head>
<body class="d-flex flex-column min-vh-100">
<?php $this->beginBody() ?>

<a class="skip-link" href="#main">Skip to main content</a>
<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1" role="main">
    <?php if (!empty($this->params['bare'])): ?>
        <div class="mw-alerts"><div class="container"><?= Alert::widget() ?></div></div>
        <?= $content ?>
    <?php else: ?>
        <div class="container">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    <?php endif ?>
</main>

<?= $this->render('_footer') ?>

<?php $whatsapp = preg_replace('/\D/', '', (string) \common\components\CustomYii::getSetting('whatsapp')); ?>
<?php if ($whatsapp !== ''): ?>
    <?php // A chat in WhatsApp with a first message ready; clients abroad often prefer it to a call. ?>
    <a class="mw-whatsapp" href="https://wa.me/<?= $whatsapp ?>?text=<?= rawurlencode(\common\components\PortalOnly::enabled() ? 'Hi, I have a question about my account.' : 'Hi, I would like to discuss a website / web application project.') ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp (opens in a new tab)" title="Chat with us on WhatsApp">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
    </a>
<?php endif; ?>
<?php $tawk = (string) \common\components\CustomYii::getSetting('tawk_chat'); ?>
<?php if (preg_match('#^[a-f0-9]{24}/[a-z0-9]+$#i', $tawk) === 1) {
    // Tawk.to live chat, bottom left: the WhatsApp and back-to-top buttons are on the right. Its script comes with
    // this page's nonce, and what it loads is allowed by 'strict-dynamic' (common/config/main.php).
    $this->registerJs('window.Tawk_API = window.Tawk_API || {}; window.Tawk_LoadStart = new Date();'
        . "window.Tawk_API.customStyle = {visibility: {desktop: {position: 'bl', xOffset: 20, yOffset: 20}, mobile: {position: 'bl', xOffset: 12, yOffset: 12}}};"
        . "(function () { var s = document.createElement('script'); s.async = true; s.src = " . \yii\helpers\Json::htmlEncode('https://embed.tawk.to/' . $tawk) . "; s.charset = 'UTF-8'; s.setAttribute('crossorigin', '*'); document.body.appendChild(s); })();", \yii\web\View::POS_END);
} ?>
<button type="button" class="mw-to-top<?= $whatsapp !== '' ? ' mw-to-top-raised' : '' ?>" aria-label="Back to top" hidden><i class="fas fa-arrow-up" aria-hidden="true"></i></button>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
