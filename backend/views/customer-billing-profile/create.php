<?php

declare(strict_types=1);

$this->title = 'Create Customer Billing Profile';

$this->params['breadcrumbs'][] = [
    'label' =>
    'Customer Billing Profiles',
    'url' =>
    ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<?=

$this->render(
        '_form',
        [
            'model' => $model,
            'usersList' => $usersList,
        ]
)
?>