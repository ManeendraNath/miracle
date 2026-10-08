<?php

use common\models\Coupons;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\CouponsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Coupons';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="coupons-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Coupons', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'coupon_id',
            'coupon_name',
            'coupon_description:ntext',
            'coupon_type',
            'coupon_code',
            //'coupon_amount',
            //'coupon_minimum_order',
            //'coupon_start_date',
            //'coupon_expire_date',
            //'uses_per_coupon',
            //'uses_per_user',
            //'coupon_active',
            //'date_created',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Coupons $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'coupon_id' => $model->coupon_id]);
                 }
            ],
        ],
    ]); ?>


</div>
