<?php

declare(strict_types=1);

use yii\helpers\Html;

$label = $model->website_url ?: '#' . $model->id;

$this->title = 'Update Maintenance: ' . $label;

$this->params['breadcrumbs'][] = [
    'label' => 'Maintenance Contracts',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $label,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update maintenance and renewal information.';
?>

<div class="maintenance-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>