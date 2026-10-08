<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Coupons $model */

$this->title = 'Create Coupons';
$this->params['breadcrumbs'][] = ['label' => 'Coupons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="coupons-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
