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
?>

<div class="hosting-update">

    <div class="mb-3">

        <h4 class="mb-1">
<?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Update hosting account, server access and renewal information.
        </div>

    </div>

<?=
$this->render(
        '_form',
        [
            'model' => $model,
        ]
)
?>

</div>