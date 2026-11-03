<?php

declare(strict_types=1);

use yii\helpers\Html;

$hostingLabel = $model->primary_domain ?: '#' . $model->id;

$this->title = 'Update Hosting: ' . $hostingLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $hostingLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update hosting account, server access and renewal information.';
?>

<div class="hosting-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>