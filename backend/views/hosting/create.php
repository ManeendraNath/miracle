<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Hosting Subscription';

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Add a client hosting account and configure its renewal information.';
?>

<div class="hosting-create">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>