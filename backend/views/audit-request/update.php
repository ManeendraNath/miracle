<?php
declare(strict_types=1);

use yii\helpers\Html;

$requestLabel = $model->name ?: '#' . $model->id;

$this->title = 'Update Audit Request: ' . $requestLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $requestLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
?>

<div class="audit-request-update">

    <div class="mb-3">

        <h4 class="mb-1">
            <?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Update client, technical and audit request information.
        </div>

    </div>

    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>