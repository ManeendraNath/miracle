<?php

declare(strict_types=1);

use yii\helpers\Html;

$couponLabel = $model->code ?: '#' . $model->id;

$this->title = 'Update Coupon: ' . $couponLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $couponLabel,
    'url' => ['view', 'id' => $model->id],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update discount rules, validity and usage limits.';
?>

<div class="coupon-update">


<?= $this->render('_form', [
    'model' => $model,
]) ?>

</div>