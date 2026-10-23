<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Domain';

$this->params['breadcrumbs'][] = [
    'label' => 'Domains Registry',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="domains-create">

    <div class="mb-3">

        <h4 class="mb-1">
<?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Add a client domain and configure its registration and renewal information.
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