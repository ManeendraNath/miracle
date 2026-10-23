<?php
declare(strict_types=1);

$this->title = 'Add Maintenance Contract';

$this->params['breadcrumbs'][] = [
    'label' => 'Maintenance Contracts',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="maintenance-create">

<?=
$this->render(
        '_form',
        [
            'model' => $model,
        ]
)
?>

</div>