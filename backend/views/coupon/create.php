<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Promotional Coupon';

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Create a discount code and configure its validity and usage limits.';
?>

<div class="coupon-create">


    <?=
    $this->render(
        '_form',
        [
                'model' => $model,
            ]
    )
    ?>

</div>