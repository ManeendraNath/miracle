<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Hosting $model */

$this->title = 'Update Hosting: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Hostings', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="hosting-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
