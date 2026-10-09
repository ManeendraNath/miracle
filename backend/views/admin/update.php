<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */

$this->title = 'Account Security Profile Settings : ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Admins', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="admin-profile-form card card-outline card-danger p-4 shadow-sm">
    <div class="mb-4">
        <h3 class="m-0 font-weight-bold text-dark"><i class="fas fa-shield-alt text-danger mr-2"></i> Admin Security Gatehouse</h3>
        <small class="text-muted">Modify account validation parameters or update active encryption passwords keys</small>
    </div>
    
    <hr>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
