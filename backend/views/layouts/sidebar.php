<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo Displays Link safely pointing to your new home dashboard route -->
    <a href="<?= Url::to(['/dashboard/index']) ?>" class="brand-link">
        <img src="<?=$assetDir?>/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Miracle Console</span>
    </a>

    <!-- Sidebar Core Workspace Wrapper -->
    <div class="sidebar">
        <!-- Sidebar User Profile Details Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?=$assetDir?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
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
            echo \hail812\adminlte\widgets\Menu::widget([
                'items' => [
                    // =====================================================================
                    // 🚀 ACTIVE BUSINESS PRODUCTION ACTIONS (Your Real Modules)
                    // =====================================================================
                    ['label' => 'CORE WORKSPACE', 'header' => true],
                    [
                        'label' => 'Dashboard', 
                        'icon' => 'tachometer-alt', 
                        'url' => ['/dashboard/index'],
                        'active' => Yii::$app->controller->id === 'dashboard'
                    ],
                    [
                        'label' => 'Manage Invoices', 
                        'icon' => 'file-invoice-dollar', 
                        'url' => ['/invoice/index'],
                        'active' => Yii::$app->controller->id === 'invoice'
                    ],
                    [
                        'label' => 'Admin Accounts', 
                        'icon' => 'users-cog', 
                        'url' => ['/admin/index'],
                        'active' => Yii::$app->controller->id === 'admin'
                    ],

                    // =====================================================================
                    // 🗂️ TEMPLATE COMPONENTS SEPARATOR (Preserved for Future Use)
                    // =====================================================================
                    ['label' => 'ORIGINAL TEMPLATES', 'header' => true],
                    [
                        'label' => 'Starter Pages Pages',
                        'icon' => 'folder-open',
                        'badge' => '<span class="right badge badge-info">2</span>',
                        'items' => [
                            ['label' => 'Dashboard Ref', 'url' => ['/dashboard/index'], 'iconStyle' => 'far'],
                            ['label' => 'Demo Layout', 'url' => ['/site/index'], 'iconStyle' => 'far'],
                        ]
                    ],
                    ['label' => 'Simple Link Page', 'icon' => 'th', 'badge' => '<span class="right badge badge-danger">New</span>'],
                    
                    ['label' => 'Yii2 SYSTEM RUNTIME', 'header' => true],
                    ['label' => 'Login Prompt', 'url' => ['/auth/login'], 'icon' => 'sign-in-alt', 'visible' => Yii::$app->user->isGuest],
                    ['label' => 'Gii Core Module', 'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank'],
                    ['label' => 'Debug Engine Toolbar', 'icon' => 'bug', 'url' => ['/debug'], 'target' => '_blank'],
                    
                    ['label' => 'MULTI LEVEL STRUCTURE REF', 'header' => true],
                    ['label' => 'Level1 Standard'],
                    [
                        'label' => 'Level1 Dropdown Container',
                        'items' => [
                            ['label' => 'Level2 Item A', 'iconStyle' => 'far'],
                            [
                                'label' => 'Level2 Item B (Nested)',
                                'iconStyle' => 'far',
                                'items' => [
                                    ['label' => 'Level3 Item Alpha', 'iconStyle' => 'far', 'icon' => 'dot-circle'],
                                    ['label' => 'Level3 Item Beta', 'iconStyle' => 'far', 'icon' => 'dot-circle'],
                                    ['label' => 'Level3 Item Gamma', 'iconStyle' => 'far', 'icon' => 'dot-circle']
                                ]
                            ],
                            ['label' => 'Level2 Item C', 'iconStyle' => 'far']
                        ]
                    ],
                    ['label' => 'Level1 End Marker'],
                    
                    ['label' => 'UI COMPONENT LABELS', 'header' => true],
                    ['label' => 'Important Notification', 'iconStyle' => 'far', 'iconClassAdded' => 'text-danger'],
                    ['label' => 'Warning Alert State', 'iconClass' => 'nav-icon far fa-circle text-warning'],
                    ['label' => 'Informational Reference', 'iconStyle' => 'far', 'iconClassAdded' => 'text-info'],
                ],
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
