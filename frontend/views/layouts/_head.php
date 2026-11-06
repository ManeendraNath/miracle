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
$this->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
$this->registerMetaTag(['property' => 'og:title', 'content' => $this->title]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $description]);
$this->registerMetaTag(['property' => 'og:image', 'content' => Url::to('@web/images/og.png', true)]);
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
if (!empty($this->params['meta_keywords'])) {
    $this->registerMetaTag(
        [
            'name' => 'keywords',
            'content' => $this->params['meta_keywords'],
        ],
    );
}
