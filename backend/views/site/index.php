<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use hail812\adminlte3\assets\AdminLteAsset;
use hail812\adminlte3\assets\PluginAsset;
use yii\helpers\Html;

$this->title = 'Dashboard';
$username = Yii::$app->user->identity?->username;

// This forces Yii to automatically publish and load all AdminLTE 3 CSS, JS, and Plugins
AdminLteAsset::register($this);

// Automatically hooks up FontAwesome Icons, jQuery overlays, and Bootstrap styles
PluginAsset::register($this, ['fontawesome']);
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <!-- Small Box Widget example from the Theme -->
        <div class="small-box bg-info">
            <div class="inner">
                <h3>150</h3>
                <p>New Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
