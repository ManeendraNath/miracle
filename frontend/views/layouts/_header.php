<?php

use common\components\CustomYii;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
?>
<header>
    <?php
    NavBar::begin([
        // 🚀 THE CURE: Places a sharp, inline logo directly inside your Header Navbar
        'brandLabel' => '<img src="' . Yii::$app->request->baseUrl . '/favicon.ico" alt="Logo" style="width:28px; height:28px; margin-right:10px; object-fit:contain; display:inline-block; vertical-align:middle;">'
        . Html::encode(CustomYii::getSetting('site_name', 'Miracle Web Technologies')),
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-md navbar-dark shadow-sm py-3',
            'style' => 'background-color: #0f172a; border-bottom: 2px solid #00a3e0 !important;'
        ],
    ]);

    $menuItems = [
        ['label' => 'Home', 'url' => ['/site/index']],
        ['label' => 'Hosting Plans', 'url' => ['/site/index', '#' => 'hosting-tiers']],
        // 💡 UPDATED ROUTE POINTER: Maps seamlessly onto our beautiful rule keys
        ['label' => 'Infrastructure Audit ⚡', 'url' => ['/site/audit'], 'linkOptions' => ['style' => 'font-weight: 600; color: #00a3e0 !important;']],
        ['label' => 'Reach Us', 'url' => ['/site/contact']],

    ];

    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Portal Access', 'url' => ['/site/login'], 'linkOptions' => ['style' => 'font-weight: 500;']];
    } else {
        $menuItems[] = '<li>'
                . Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline'])
                . Html::submitButton(
                        'Sign Out (' . Html::encode(Yii::$app->user->identity->username) . ')',
                        ['class' => 'btn btn-link nav-link logout', 'style' => 'font-weight: 500; border: none;']
                )
                . Html::endForm()
                . '</li>';
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto gap-2'],
        'items' => $menuItems,
    ]);

    NavBar::end();
    ?>
</header>
