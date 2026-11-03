<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\AdminNotification;

/** @var AdminNotification[] $adminNotifications */
$adminNotifications = AdminNotification::visibleTo(Yii::$app->user->identity)
        ->orderBy([
            'is_read' => SORT_ASC,
            'created_at' => SORT_DESC,
        ])
        ->limit(10)
        ->all();

$adminNotificationCount = AdminNotification::visibleTo(Yii::$app->user->identity)
        ->andWhere([
            'is_read' => 0,
        ])
        ->count();
?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <?php if ($adminNotificationCount > 0): ?>
                    <span class="badge badge-danger navbar-badge">
                        <?= $adminNotificationCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-header">
                    <?= $adminNotificationCount ?>
                    Unread Notification<?= $adminNotificationCount !== 1 ? 's' : '' ?>
                </span>
                <div class="dropdown-divider"></div>
                <?php if (empty($adminNotifications)): ?>
                    <div class="dropdown-item text-center text-muted py-3">
                        No notifications available
                    </div>
                <?php else: ?>
                    <?php foreach ($adminNotifications as $notification): ?>
                        <a href="<?= Url::to(['/admin-notification/read', 'id' => $notification->id]) ?>" class="dropdown-item">
                            <div class="d-flex justify-content-between">
                                <strong><?= Html::encode($notification->title) ?></strong>
                        <?php if (!$notification->is_read): ?>
                                    <span class="badge badge-danger">New</span>
                        <?php endif; ?>
                            </div>
                            <div class="text-muted text-sm mt-1" style="white-space: normal; line-height: 1.4;">
                                <?= Html::encode(mb_strimwidth($notification->message, 0, 80, '...')) ?>
                            </div>
                            <div class="text-muted text-xs mt-1">
                        <?= Yii::$app->formatter->asRelativeTime($notification->created_at) ?>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= Url::to(['/admin-notification/index']) ?>" class="dropdown-item dropdown-footer">View All Notifications</a>
            </div>
        </li>
        <li class="nav-item">
<?= Html::a('<i class="fas fa-user-circle"></i>', ['/admin/account'], ['class' => 'nav-link', 'title' => 'My account']) ?>
        </li>
        <li class="nav-item">
<?= Html::a('<i class="fas fa-sign-out-alt"></i>', ['/auth/logout'], ['data-method' => 'post', 'class' => 'nav-link', 'title' => 'Sign out']) ?>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
    </ul>
</nav>
<!-- /.navbar -->