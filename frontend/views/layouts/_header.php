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
        // One account menu: a user icon that opens Dashboard and Sign out (in the phone menu it opens in place).
        $username = Html::encode(Yii::$app->user->identity->username);
        $menuItems[] = '<li class="nav-item dropdown">'
                . '<a class="nav-link dropdown-toggle" href="#" id="account-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="My account">'
                . '<i class="fas fa-user-circle fa-lg" aria-hidden="true"></i><span class="nav-icon-text">My account</span></a>'
                . '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="account-menu">'
                . '<li><span class="dropdown-item-text small text-muted">Signed in as <strong>' . $username . '</strong></span></li>'
                . '<li><hr class="dropdown-divider"></li>'
                . '<li>' . Html::a('<i class="fas fa-th-large fa-fw me-2" aria-hidden="true"></i>Dashboard', ['/dashboard/index'], ['class' => 'dropdown-item']) . '</li>'
                . '<li>' . Html::beginForm(['/site/logout'], 'post')
                . Html::submitButton('<i class="fas fa-sign-out-alt fa-fw me-2" aria-hidden="true"></i>Sign out', ['class' => 'dropdown-item logout'])
                . Html::endForm() . '</li>'
                . '</ul></li>';
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto align-items-xl-center flex-xl-nowrap'],
        'items' => $menuItems,
    ]);

    NavBar::end();
    ?>
</header>
