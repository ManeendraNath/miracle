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
?>

<div class="maintenance-update">

    <div class="mb-3">

        <h4 class="mb-1">
<?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Update maintenance and renewal information.
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