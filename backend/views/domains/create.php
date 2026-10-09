<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Domains $model */

$this->title = 'Create Domains';
$this->params['breadcrumbs'][] = ['label' => 'Domains', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
