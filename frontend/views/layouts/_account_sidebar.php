<?php

use yii\helpers\Html;
use yii\helpers\Url;

$currentController = Yii::$app->controller->id;

$items = [
    ['label' => 'Dashboard', 'icon' => 'fas fa-home', 'url' => ['/dashboard/index'], 'controller' => 'dashboard'],
    ['label' => 'My Services', 'icon' => 'fas fa-cubes', 'url' => ['/service/index'], 'controller' => 'service'],
    ['label' => 'Invoices', 'icon' => 'fas fa-file-invoice', 'url' => ['/invoice/index'], 'controller' => 'invoice'],
    ['label' => 'Payments', 'icon' => 'fas fa-credit-card', 'url' => ['/payment/index'], 'controller' => 'payment'],
    ['label' => 'Renewals', 'icon' => 'fas fa-sync-alt', 'url' => ['/renewal/index'], 'controller' => 'renewal'],
    ['label' => 'Support Tickets', 'icon' => 'fas fa-headset', 'url' => ['/support/index'], 'controller' => 'support'],
    ['label' => 'Notifications', 'icon' => 'fas fa-bell', 'url' => ['/notification/index'], 'controller' => 'notification'],
    ['label' => 'Security', 'icon' => 'fas fa-shield-alt', 'url' => ['/security/index'], 'controller' => 'security'],
    ['label' => 'My Profile', 'icon' => 'fas fa-user', 'url' => ['/account/profile'], 'controller' => 'account', 'action' => ['profile', 'change-password', 'preferences']],
    ['label' => 'Activity Timeline', 'icon' => 'fas fa-history', 'url' => ['/account/activity'], 'controller' => 'account', 'action' => ['activity']],
];
$actionId = Yii::$app->controller->action->id;
$currentLabel = 'Menu';
foreach ($items as &$item) {
    $item['active'] = $currentController === $item['controller'] && (!isset($item['action']) || in_array($actionId, $item['action'], true));
    if ($item['active']) {
        $currentLabel = $item['label'];
    }
}
unset($item);
?>

<div class="account-sidebar card shadow-sm border-0">
    <div class="card-header border-0 d-flex align-items-center" style="background:linear-gradient(135deg, #0b1f4d, #1565c0); color:#fff;">
        <div class="flex-grow-1">
            <div class="fw-bold">My Account</div>
            <small style="opacity:.85;">Manage your services</small>
        </div>
        <?php /* Below lg the menu starts collapsed; this button shows the current page and opens it. */ ?>
        <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#account-menu" aria-expanded="false" aria-controls="account-menu">
            <?= Html::encode($currentLabel) ?> <i class="fas fa-chevron-down ms-1"></i>
        </button>
    </div>
    <div id="account-menu" class="card-body collapse d-lg-block">
        <?php foreach ($items as $item): ?>
            <a href="<?= Url::to($item['url']) ?>" class="list-group-item list-group-item-action d-flex align-items-center <?= $item['active'] ? 'active' : '' ?>">
                <i class="<?= $item['icon'] ?> fa-fw me-2"></i>
                <span><?= Html::encode($item['label']) ?></span>
                <?php if ($item['controller'] === 'notification' && !empty($unreadNotifications)): ?>
                    <span class="badge bg-danger ms-auto"><?= $unreadNotifications ?></span>
                <?php endif; ?>
                <?php if ($item['controller'] === 'support' && !empty($openSupportTickets)): ?>
                    <span class="badge bg-warning text-dark ms-auto"><?= $openSupportTickets ?></span>
                <?php endif; ?>
            </a>
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