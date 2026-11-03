<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Audit Request';

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Record a client website audit request and its technical details.';
?>

<div class="audit-request-create">


    <?= $this->render('_form', [
                'model' => $model,
    ]) ?>

</div>