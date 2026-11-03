<?php

declare(strict_types=1);

use yii\helpers\Html;

$userLabel = $model->username ?: '#' . $model->id;

$this->title = 'Update User: ' . $userLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Users',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $userLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update customer account information and account status.';
?>

<div class="user-update">


    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>