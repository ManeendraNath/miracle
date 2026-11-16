<?php

use common\components\CustomYii;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

?>
<header class="sticky-top mw-sticky">
    <?php
    NavBar::begin([
        'brandLabel' => Html::img('@web/images/logo.svg', ['alt' => '', 'width' => 49, 'height' => 36])
            . Html::encode(CustomYii::getSetting('site_name', 'Miracle Web Technologies')),
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar navbar-expand-xl mw-header py-2'],
        'togglerOptions' => ['aria-label' => 'Open menu'],
    ]);

    $menuItems = [
        ['label' => 'What we build', 'url' => ['/site/index', '#' => 'services'], 'active' => false],
        ['label' => 'Our work', 'url' => ['/site/index', '#' => 'work'], 'active' => false],
        ['label' => 'Blog', 'url' => ['/blog/index'], 'active' => Yii::$app->controller->id === 'blog'],
        ['label' => 'About', 'url' => ['/site/about']],
        ['label' => 'Contact', 'url' => ['/site/contact']],
    ];

    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Log in', 'url' => ['/site/login']];
        $menuItems[] = '<li class="nav-item ms-xl-2">' . Html::a('Get a quote', ['/site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-primary']) . '</li>';
    } else {
        // Icons on the one-line desktop menu (name in the tooltip and for screen readers); icon and name in the phone menu.
        $iconLabel = static fn(string $icon, string $text): string => '<i class="fas ' . $icon . ' fa-fw" aria-hidden="true"></i>'
            . '<span class="nav-icon-text">' . $text . '</span>';
        $signOut = 'Sign out (' . Html::encode(Yii::$app->user->identity->username) . ')';
        $menuItems[] = ['label' => $iconLabel('fa-tachometer-alt', 'Dashboard'), 'encode' => false, 'url' => ['/dashboard/index'], 'linkOptions' => ['title' => 'Dashboard']];
        $menuItems[] = '<li class="nav-item">'
                . Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline'])
                . Html::submitButton($iconLabel('fa-sign-out-alt', $signOut), ['class' => 'btn btn-link nav-link logout', 'title' => $signOut])
                . Html::endForm()
                . '</li>';
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto align-items-xl-center flex-xl-nowrap'],
        'items' => $menuItems,
    ]);

    NavBar::end();
    ?>
</header>
