<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Coupons $model */

$this->title = 'Update Coupons: ' . $model->coupon_id;
$this->params['breadcrumbs'][] = ['label' => 'Coupons', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->coupon_id, 'url' => ['view', 'coupon_id' => $model->coupon_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="coupons-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
