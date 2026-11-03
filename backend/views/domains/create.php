<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Domain';

$this->params['breadcrumbs'][] = [
    'label' => 'Domains Registry',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Add a client domain and configure its registration and renewal information.';
?>

<div class="domains-create">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>