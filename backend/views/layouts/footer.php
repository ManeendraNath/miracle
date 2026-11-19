<?php

$founded = (int) \common\components\CustomYii::getSetting('founded_year');
?>
<footer class="main-footer text-sm">
    <strong>&copy; <?= $founded > 0 && $founded < (int) date('Y') ? $founded . '-' : '' ?><?= date('Y') ?> <?= \yii\helpers\Html::encode(\common\components\CustomYii::businessName()) ?>.</strong> All rights reserved.
</footer>
