<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */

$this->title = 'Create System User Account';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">

    <h3><i class="fas fa-user-plus mr-2 text-primary"></i> Add Client Profile</h3>
    <hr>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
