<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Audit Request';

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="audit-request-create">

    <div class="mb-3">

        <h4 class="mb-1">
            <?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Record a client website audit request and its technical details.
        </div>

    </div>

    <?= $this->render('_form', [
                'model' => $model,
    ]) ?>

</div>