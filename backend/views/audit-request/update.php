<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */

$this->title = 'Update Audit Request: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Audit Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="audit-request-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
