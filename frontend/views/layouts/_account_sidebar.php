<?php

use yii\helpers\Html;
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">My Account</div>
    <div class="list-group list-group-flush">
        <?= Html::a('Dashboard', ['/dashboard/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Domains', ['/domain/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Hosting', ['/hosting/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Maintenance', ['/maintenance/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Invoices', ['/invoice/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Payments', ['/payment/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Support Tickets', ['/support/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Notifications', ['/notification/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Security', ['/security/index'], ['class' => 'list-group-item list-group-item-action']) ?>
        <?= Html::a('Preferences', ['/preference/index'], ['class' => 'list-group-item list-group-item-action']) ?>
    </div>
</div>