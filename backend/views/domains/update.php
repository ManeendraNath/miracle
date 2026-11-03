<?php

declare(strict_types=1);

use yii\helpers\Html;

$domainLabel = $model->domain_url ?: '#' . $model->id;

$this->title = 'Update Domain: ' . $domainLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Domains Registry',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $domainLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update domain registration and renewal information.';
?>

<div class="domains-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>