<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */

$this->title = 'Create System User Account';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">

    <hr>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
