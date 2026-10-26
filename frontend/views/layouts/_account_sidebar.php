<?php

use yii\helpers\Html;

$currentController = Yii::$app->controller->id;

$items = [
    ['label' => 'Dashboard', 'icon' => 'fas fa-home', 'url' => ['/dashboard/index'], 'controller' => 'dashboard'],
    ['label' => 'My Services', 'icon' => 'fas fa-cubes', 'url' => ['/service/index'], 'controller' => 'service'],
    ['label' => 'Invoices', 'icon' => 'fas fa-file-invoice', 'url' => ['/invoice/index'], 'controller' => 'invoice'],
    ['label' => 'Payments', 'icon' => 'fas fa-credit-card', 'url' => ['/payment/index'], 'controller' => 'payment'],
    ['label' => 'Renewal History', 'icon' => 'fas fa-history', 'url' => ['/renewal/index'], 'controller' => 'renewal'],
    ['label' => 'Support Tickets', 'icon' => 'fas fa-headset', 'url' => ['/support/index'], 'controller' => 'support'],
    ['label' => 'Notifications', 'icon' => 'fas fa-bell', 'url' => ['/notification/index'], 'controller' => 'notification'],
    ['label' => 'Security', 'icon' => 'fas fa-shield-alt', 'url' => ['/security/index'], 'controller' => 'security'],
    ['label' => 'My Profile', 'icon' => 'fas fa-user', 'url' => ['/account/profile'], 'controller' => 'account'],
];
?>

<div class="account-sidebar card shadow-sm border-0">
    <div class="card-header border-0" style="background:linear-gradient(135deg, #0b1f4d, #1565c0); color:#fff;">
        <div class="fw-bold">My Account</div>
        <small style="opacity:.85;">Manage your services</small>
    </div>
    <div class="card-body">
        <?php if ($unreadNotifications): ?>
            <span class="badge bg-danger float-end">
                <?= $unreadNotifications ?>
            </span>
        <?php endif; ?>

        <?php if ($openSupportTickets): ?>
            <span class="badge bg-warning text-dark float-end">
                <?= $openSupportTickets ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($items as $item): ?>
            <?php
            $active = $currentController === $item['controller'];
            ?>
            <?= Html::a('<i class="' . $item['icon'] . ' me-2"></i>' . Html::encode($item['label']), $item['url'], ['class' => 'list-group-item ' . 'list-group-item-action ' . ($active ? 'active' : ''), 'encode' => false]) ?>
        <?php endforeach; ?>
        <?=
        Html::a(
                '<i class="fas fa-sign-out-alt me-2"></i> Sign Out',
                ['/site/logout'],
                [
                    'class' => 'list-group-item ' . 'list-group-item-action ' . 'text-danger',
                    'data' => ['method' => 'post'],
                    'encode' => false,
                ]
        )
        ?>
    </div>
</div>