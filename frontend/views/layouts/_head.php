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
$route = Yii::$app->controller?->getRoute();
// Title and description staff set for this page (Admin → SEO), used as written. Not for a filtered blog list
// (a category, tag or month, or page 2): those keep their own.
$seo = $route !== null && (!in_array($route, ['blog/index', 'store/index', 'store/services', 'store/plans'], true) || Yii::$app->request->getQueryParams() === []) ? \common\models\SeoPage::forRoute($route) : null;
if ($seo !== null && (string) $seo->title !== '') {
    $this->title = $seo->title;
}
if ($seo !== null && (string) $seo->description !== '') {
    $this->params['meta_description'] = $seo->description;
}
// Sign-in and account pages: nothing for searchers there.
if ($route !== null && str_starts_with($route, 'site/') && in_array(substr($route, 5), \common\models\SeoPage::PRIVATE_ACTIONS, true)) {
    $this->params['robots'] = 'noindex, follow';
}
foreach (['google-site-verification' => 'google_site_verification', 'msvalidate.01' => 'bing_site_verification'] as $metaName => $setting) {
    $code = (string) CustomYii::getSetting($setting);
    if ($code !== '') {
        $this->registerMetaTag(['name' => $metaName, 'content' => $code]);
    }
}
if (!str_contains((string) $this->title, $siteName)) {
    $this->title = trim($this->title . ' | ' . $siteName, ' |');
}
// Search engines cut titles at about 60 characters: a long one drops the site name rather than its own words.
$suffix = ' | ' . $siteName;
if (mb_strlen((string) $this->title) > 60 && str_ends_with((string) $this->title, $suffix) && mb_strlen((string) $this->title) > mb_strlen($suffix)) {
    $this->title = mb_substr((string) $this->title, 0, -mb_strlen($suffix));
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
