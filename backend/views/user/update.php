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
?>

<div class="user-update">

    <div class="mb-3">

        <h4 class="mb-1">
            <?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Update customer account information and account status.
        </div>

    </div>

    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>