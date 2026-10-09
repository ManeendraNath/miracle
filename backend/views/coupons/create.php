<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Coupons $model */

$this->title = 'Create Coupons';
$this->params['breadcrumbs'][] = ['label' => 'Coupons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="coupons-create">
    
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
