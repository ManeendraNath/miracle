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
?>

<div class="domains-update">

    <div class="mb-3">

        <h4 class="mb-1">
<?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Update domain registration and renewal information.
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