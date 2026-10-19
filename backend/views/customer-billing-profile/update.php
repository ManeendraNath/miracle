<?php

declare(strict_types=1);

$this->title = 'Update Customer Billing Profile';

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