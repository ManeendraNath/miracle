<?php
declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Promotional Coupon';

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="coupon-create">

    <div class="mb-3">

        <h4 class="mb-1">
<?=
Html::encode(
        $this->title
)
?>
        </h4>

        <div class="text-muted small">
            Create a discount code and configure its validity and usage limits.
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