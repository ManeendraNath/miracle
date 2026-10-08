<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$items = [
    [
        'label' => 'Home',
        'url' => ['/admin/dashboard'],
    ],
    [
        'label' => 'Login',
        'url' => ['/admin/auth/login'],
        'visible' => Yii::$app->user->isGuest,
    ],
    [
        'label' => 'Logout (' . Html::encode(Yii::$app->user->identity?->username) . ')',
        'url' => ['/admin/auth/logout'],
        'linkOptions' => [
            'data-method' => 'post',
            'class' => 'logout',
        ],
        'visible' => !Yii::$app->user->isGuest,
    ],
];
?>
<header id="header">
    <?php NavBar::begin(
        [
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>
