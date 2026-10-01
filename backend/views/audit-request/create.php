<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */

$this->title = 'Create Audit Request';
$this->params['breadcrumbs'][] = ['label' => 'Audit Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="audit-request-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
