<?php
declare(strict_types=1);

use yii\helpers\Html;

$couponLabel = $model->code ?: '#' . $model->id;

$this->title = 'Update Coupon: ' . $couponLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $couponLabel,
    'url' => [
        'view',
        'id' =>
        $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
?>

<div class="coupon-update">

    <div class="mb-3">

        <h4 class="mb-1">
<?=
Html::encode(
        $this->title
)
?>
        </h4>

        <div class="text-muted small">
            Update discount rules, validity and usage limits.
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