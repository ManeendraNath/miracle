<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Hosting Subscription';

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="hosting-create">

    <div class="mb-3">

        <h4 class="mb-1">
<?= Html::encode($this->title) ?>
        </h4>

        <div class="text-muted small">
            Add a client hosting account and configure its renewal information.
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