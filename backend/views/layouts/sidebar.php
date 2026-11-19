<?php

use yii\helpers\Url;
use yii\helpers\Html;

// Reader comments waiting for staff, shown next to Comments.
$waitingComments = (int) \common\models\BlogComment::onLivePosts()->andWhere(['status' => \common\models\BlogComment::STATUS_PENDING])->count();

?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo Displays Link -->
    <!-- One full logo (white lettering for the dark sidebar): the collapsed sidebar crops it to the round mark, so
         nothing swaps while the sidebar slides. -->
    <a href="<?= Url::to(['/dashboard/index']) ?>" class="brand-link brand-logo" title="Miracle Web Technologies">
        <img src="<?= Url::to('@web/images/logo-full-white.svg') ?>" alt="Miracle Web Technologies" class="brand-full">
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
            // Hide links the signed-in role cannot open; a group left with none is hidden by the menu itself.
            $filterMenu = static function (array $items) use (&$filterMenu): array {
                $identity = Yii::$app->user->identity;
                foreach ($items as &$item) {
                    if (isset($item['items'])) {
                        $item['items'] = $filterMenu($item['items']);
                    } elseif (isset($item['url'][0]) && !isset($item['visible']) && preg_match('#^/([\w-]+)(?:/([\w-]+))?#', $item['url'][0], $m)) {
                        $item['visible'] = \backend\controllers\BaseController::canAccess($identity, $m[1], $m[2] ?? 'index');
                    }
                }
                unset($item);

                return $items;
            };
            echo \hail812\adminlte\widgets\Menu::widget([
                'options' => ['class' => 'nav nav-pills nav-sidebar nav-child-indent flex-column', 'data-widget' => 'treeview', 'role' => 'menu', 'data-accordion' => 'true'],
                'items' => $filterMenu([
                    // =====================================================================
                    // 🚀 1. CORE WORKSPACE & CRM MONITORING
                    // =====================================================================
                    [
                        'label' => 'Dashboard',
                        'icon' => 'tachometer-alt',
                        'url' => ['/dashboard/index'],
                        'active' => Yii::$app->controller->id === 'dashboard'
                    ],
                    [
                        'label' => 'Leads & website',
                        'icon' => 'bullhorn',
                        'items' => [
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
                        [
                            'label' => 'SEO',
                            'icon' => 'search',
                            'url' => ['/seo-page/index'],
                            'active' => Yii::$app->controller->id === 'seo-page'
                        ],
                        ],
                    ],
                    [
                        'label' => 'Themes & Plugins',
                        'icon' => 'store',
                        'url' => ['/product/index'],
                        'active' => Yii::$app->controller->id === 'product'
                    ],
                    [
                        'label' => 'Store Sales',
                        'icon' => 'receipt',
                        'url' => ['/product-order/index'],
                        'active' => Yii::$app->controller->id === 'product-order'
                    ],
                    // =====================================================================
                    // 📝 1.0.1. BLOG (the public blog at /blog)
                    // =====================================================================
                    [
                        'label' => 'Blog',
                        'icon' => 'blog',
                        'badge' => $waitingComments > 0 ? '<span class="right badge badge-warning" title="Comments waiting for approval">' . $waitingComments . '</span>' : '',
                        'items' => [
                        [
                            'label' => 'Posts',
                            'icon' => 'newspaper',
                            'url' => ['/blog-post/index'],
                            'active' => Yii::$app->controller->id === 'blog-post' && Yii::$app->controller->action->id !== 'create'
                        ],
                        [
                            'label' => 'Write a post',
                            'icon' => 'pen-nib',
                            'url' => ['/blog-post/create'],
                            'active' => Yii::$app->controller->id === 'blog-post' && Yii::$app->controller->action->id === 'create'
                        ],
                        [
                            'label' => 'Categories',
                            'icon' => 'tags',
                            'url' => ['/blog-category/index'],
                            'active' => Yii::$app->controller->id === 'blog-category'
                        ],
                        [
                            'label' => 'Comments',
                            'badge' => $waitingComments > 0 ? '<span class="right badge badge-warning" title="Waiting for approval">' . $waitingComments . '</span>' : '',
                            'icon' => 'comments',
                            'url' => ['/blog-comment/index'],
                            'active' => Yii::$app->controller->id === 'blog-comment'
                        ],
                        [
                            'label' => 'Media',
                            'icon' => 'images',
                            'url' => ['/blog-media/index'],
                            'active' => Yii::$app->controller->id === 'blog-media'
                        ],
                        [
                            'label' => 'Subscribers',
                            'icon' => 'envelope-open-text',
                            'url' => ['/blog-subscriber/index'],
                            'active' => Yii::$app->controller->id === 'blog-subscriber'
                        ],
                        ],
                    ],
                    // =====================================================================
                    // 🚀 1.1. CUSTOMER SUPPORT
                    // =====================================================================
                    [
                        'label' => 'Customer support',
                        'icon' => 'headset',
                        'items' => [
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
                        ],
                    ],
                    // =====================================================================
                    // 💰 2. FINANCIAL FINANCE & MARKETING OPERATIONS
                    // =====================================================================
                    [
                        'label' => 'Finance',
                        'icon' => 'rupee-sign',
                        'items' => [
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
                        ],
                    ],
                    // =====================================================================
                    // 🌐 3. INFRASTRUCTURE & ACTIVE CLIENT ASSETS
                    // =====================================================================
                    [
                        'label' => 'Services',
                        'icon' => 'server',
                        'items' => [
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
                        ],
                    ],
                    // =====================================================================
                    // 👥 4. USER ACCESS MANAGEMENT CONTROL
                    // =====================================================================
                    [
                        'label' => 'Administration',
                        'icon' => 'user-shield',
                        'items' => [
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
                        ],
                    ],
                    [
                        'label' => 'Developer',
                        'icon' => 'code',
                        'visible' => YII_ENV_DEV,
                        'items' => [
                            ['label' => 'Gii', 'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank'],
                            ['label' => 'Debug Toolbar', 'icon' => 'bug', 'url' => ['/debug'], 'target' => '_blank'],
                        ],
                    ],
                ]),
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
</aside>
