<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Coupons $model */

$this->title = $model->coupon_id;
$this->params['breadcrumbs'][] = ['label' => 'Coupons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="coupons-view">

    <p>
        <?= Html::a('Update', ['update', 'coupon_id' => $model->coupon_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'coupon_id' => $model->coupon_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'coupon_id',
            'coupon_name',
            'coupon_description:ntext',
            'coupon_type',
            'coupon_code',
            'coupon_amount',
            'coupon_minimum_order',
            'coupon_start_date',
            'coupon_expire_date',
            'uses_per_coupon',
            'uses_per_user',
            'coupon_active',
            'date_created',
        ],
    ]) ?>

</div>
