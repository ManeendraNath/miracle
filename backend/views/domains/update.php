<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Domains $model */

$this->title = 'Update Domains: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Domains', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="domains-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
