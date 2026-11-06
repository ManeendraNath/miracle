<?php

use yii\helpers\Url;
use yii\helpers\Html;

?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo Displays Link -->
    <a href="<?= Url::to(['/dashboard/index']) ?>" class="brand-link">
        <img src="<?= $assetDir ?>/img/AdminLTELogo.png" alt="Miracle" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Miracle Console</span>
    </a>

    <!-- Sidebar Wrapper -->
    <div class="sidebar">
        <!-- Sidebar User Profile Details Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= $assetDir ?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= Html::encode(Yii::$app->user->identity->username ?? 'Administrator') ?></a>
            </div>
        </div>

        <!-- Sidebar Search Form Component -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Active Menu Navigation Widget -->
        <nav class="mt-2">
            <?php
            // Hide links the signed-in role cannot open, then section headers left empty.
            $filterMenu = static function (array $items): array {
                $identity = Yii::$app->user->identity;
                foreach ($items as &$item) {
                    if (isset($item['url'][0]) && !isset($item['visible']) && preg_match('#^/([\w-]+)(?:/([\w-]+))?#', $item['url'][0], $m)) {
                        $item['visible'] = \backend\controllers\BaseController::canAccess($identity, $m[1], $m[2] ?? 'index');
                    }
                }
                unset($item);
                $out = [];
                foreach ($items as $i => $item) {
                    if (!empty($item['header'])) {
                        $next = array_slice($items, $i + 1);
                        $end = array_search(true, array_map(static fn($x): bool => !empty($x['header']), $next), true);
                        $section = array_slice($next, 0, $end === false ? null : $end);
                        if (!array_filter($section, static fn($x): bool => $x['visible'] ?? true)) {
                            continue;
                        }
                    }
                    $out[] = $item;
                }
                return $out;
            };
            echo \hail812\adminlte\widgets\Menu::widget([
                'options' => ['class' => 'nav nav-pills nav-sidebar flex-column', 'data-widget' => 'treeview', 'role' => 'menu', 'data-accordion' => 'false'],
                'items' => $filterMenu([
                    // =====================================================================
                    // 🚀 1. CORE WORKSPACE & CRM MONITORING
                    // =====================================================================
                    ['label' => 'WORKSPACE', 'header' => true],
                    [
                        'label' => 'Dashboard',
                        'icon' => 'tachometer-alt',
                        'url' => ['/dashboard/index'],
                        'active' => Yii::$app->controller->id === 'dashboard'
                    ],
                    [
                        'label' => 'Audit Requests',
                        'icon' => 'id-card',
                        'url' => ['/audit-request/index'],
                        'active' => Yii::$app->controller->id === 'audit-request'
                    ],
                    [
                        'label' => 'Leads CRM',
                        'icon' => 'envelope-open-text',
                        'url' => ['/lead/index'],
                        'active' => Yii::$app->controller->id === 'lead'
                    ],
                    [
                        'label' => 'Testimonials',
                        'icon' => 'quote-left',
                        'url' => ['/testimonial/index'],
                        'active' => Yii::$app->controller->id === 'testimonial'
                    ],
                    // =====================================================================
                    // 🚀 1.1. CUSTOMER SUPPORT
                    // =====================================================================
                    ['label' => 'CUSTOMER SUPPORT', 'header' => true],
                    [
                        'label' => 'Support Requests',
                        'icon' => 'headset',
                        'url' => ['/support-ticket/index'],
                        'active' => Yii::$app->controller->id === 'support-ticket',
                    ],
                    [
                        'label' => 'Admin Notifications',
                        'icon' => 'bell',
                        'url' => ['/admin-notification/index'],
                        'active' => Yii::$app->controller->id === 'admin-notification',
                    ],
                    [
                        'label' => 'Customer Preferences',
                        'icon' => 'user-cog',
                        'url' => ['/customer-preference/index'],
                        'active' => Yii::$app->controller->id === 'customer-preference',
                    ],
                    // =====================================================================
                    // 💰 2. FINANCIAL FINANCE & MARKETING OPERATIONS
                    // =====================================================================
                    ['label' => 'FINANCE & MARKETING', 'header' => true],
                    [
                        'label' => 'Manage Invoices',
                        'icon' => 'file-invoice-dollar',
                        'url' => ['/invoice/index'],
                        'active' => Yii::$app->controller->id === 'invoice'
                    ],
                    [
                        'label' => 'Billing Profiles',
                        'icon' => 'address-card',
                        'url' => ['/customer-billing-profile/index'],
                        'active' => Yii::$app->controller->id === 'customer-billing-profile',
                    ],
                    [
                        'label' => 'Transactions',
                        'icon' => 'chart-line',
                        'url' => ['/transaction/index'],
                        'active' => Yii::$app->controller->id === 'transaction'
                    ],
                    [
                        'label' => 'Coupons',
                        'icon' => 'tags',
                        'url' => ['/coupon/index'],
                        'active' => Yii::$app->controller->id === 'coupon'
                    ],
                    [
                        'label' => 'Reports',
                        'icon' => 'file-csv',
                        'url' => ['/report/index'],
                        'active' => Yii::$app->controller->id === 'report',
                    ],
                    [
                        'label' => 'Razorpay Test',
                        'icon' => 'vial',
                        'url' => ['/razorpay-test/index'],
                        'active' => Yii::$app->controller->id === 'razorpay-test',
                        // Admin+ only, and only while test keys are configured.
                        'visible' => \backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'razorpay-test')
                            && (new \common\services\payment\RazorpayService())->isTestMode(),
                    ],
                    // =====================================================================
                    // 🌐 3. INFRASTRUCTURE & ACTIVE CLIENT ASSETS
                    // =====================================================================
                    ['label' => 'SERVICES', 'header' => true],
                    [
                        'label' => 'Domains',
                        'icon' => 'globe',
                        'url' => ['/domains/index'],
                        'active' => Yii::$app->controller->id === 'domains'
                    ],
                    [
                        'label' => 'Web Hosting',
                        'icon' => 'cloud',
                        'url' => ['/hosting/index'],
                        'active' => Yii::$app->controller->id === 'hosting'
                    ],
                    [
                        'label' => 'Maintenance (AMC)',
                        'icon' => 'tools',
                        'url' => ['/maintenance/index'],
                        'active' => Yii::$app->controller->id === 'maintenance'
                    ],
                    [
                        'label' => 'Renewals',
                        'icon' => 'calendar-check',
                        'url' => ['/renewal-monitoring/index'],
                        'active' => Yii::$app->controller->id === 'renewal-monitoring',
                    ],
                    // =====================================================================
                    // 👥 4. USER ACCESS MANAGEMENT CONTROL
                    // =====================================================================
                    ['label' => 'ADMINISTRATION', 'header' => true],
                    [
                        'label' => 'Clients',
                        'icon' => 'users',
                        'url' => ['/user/index'],
                        'active' => Yii::$app->controller->id === 'user'
                    ],
                    [
                        'label' => 'Login History',
                        'icon' => 'shield-alt',
                        'url' => ['/user-login-history/index'],
                        'active' => Yii::$app->controller->id === 'user-login-history',
                    ],
                    [
                        'label' => 'Admin Accounts',
                        'icon' => 'users-cog',
                        'url' => ['/admin/index'],
                        'active' => Yii::$app->controller->id === 'admin' && Yii::$app->controller->action->id !== 'cron-log'
                    ],
                    [
                        'label' => 'Cron Jobs & Logs',
                        'icon' => 'terminal',
                        'url' => ['/admin/cron-log'],
                        'active' => Yii::$app->controller->id === 'admin' && Yii::$app->controller->action->id === 'cron-log'
                    ],
                    [
                        'label' => 'Site Settings',
                        'icon' => 'sliders-h',
                        'url' => ['/site-setting/index'],
                        'active' => Yii::$app->controller->id === 'site-setting'
                    ],
                    ['label' => 'DEVELOPER', 'header' => true, 'visible' => YII_ENV_DEV],
                    ['label' => 'Gii', 'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank', 'visible' => YII_ENV_DEV],
                    ['label' => 'Debug Toolbar', 'icon' => 'bug', 'url' => ['/debug'], 'target' => '_blank', 'visible' => YII_ENV_DEV],
                ]),
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
</aside>
