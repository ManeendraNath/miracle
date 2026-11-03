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
$this->params['subtitle'] = 'Update client, technical and audit request information.';
?>

<div class="audit-request-update">


    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>