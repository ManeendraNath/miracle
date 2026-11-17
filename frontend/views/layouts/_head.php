<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use common\components\CustomYii;
use frontend\assets\AppAsset;
use yii\helpers\Url;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(
    ['charset' => Yii::$app->charset],
    'charset',
);
$this->registerMetaTag(
    [
        'name' => 'viewport',
        'content' => 'width=device-width, initial-scale=1',
    ],
);
$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
if (!str_contains((string) $this->title, $siteName)) {
    $this->title = trim($this->title . ' | ' . $siteName, ' |');
}
$description = $this->params['meta_description']
    ?? 'Custom shopping carts, web applications and websites, built to your brief.';
$this->registerMetaTag(['name' => 'description', 'content' => $description]);
$this->registerMetaTag(['property' => 'og:site_name', 'content' => $siteName]);
$this->registerMetaTag(['property' => 'og:type', 'content' => $this->params['og_type'] ?? 'website']);
$this->registerMetaTag(['property' => 'og:title', 'content' => $this->title]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $description]);
$this->registerMetaTag(['property' => 'og:image', 'content' => $this->params['og_image'] ?? Url::to('@web/images/og.png', true)]);
// Every page names its one address, so /site/about, /index.php, www. or ?subject=… are not counted as separate
// pages by search engines. Absolute links use the configured site address (frontendBaseUrl), whatever the host.
$canonical = $this->params['canonical'] ?? null;
// Not for one-time links (password reset): their address must not be handed to search engines.
if ($canonical === null && Yii::$app->controller !== null && Yii::$app->errorHandler->exception === null && !isset(Yii::$app->controller->actionParams['token'])) {
    $route = Yii::$app->controller->getRoute();
    $canonical = $route === 'site/index' ? Url::home(true) : Url::to(array_merge(['/' . $route], Yii::$app->controller->actionParams), true);
}
if ($canonical) {
    $this->registerLinkTag(['rel' => 'canonical', 'href' => $canonical]);
    $this->registerMetaTag(['property' => 'og:url', 'content' => $canonical]);
}
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
if (!empty($this->params['robots'])) {
    $this->registerMetaTag(['name' => 'robots', 'content' => $this->params['robots']]);
}
if (!empty($this->params['meta_keywords'])) {
    $this->registerMetaTag(
        [
            'name' => 'keywords',
            'content' => $this->params['meta_keywords'],
        ],
    );
}
