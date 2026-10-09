<?php

use common\models\Invoice;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\InvoiceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Invoices';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-index">

    <p>
        <?= Html::a('Create Invoice', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]);  ?>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'user_id',
            'invoice_number',
            'client_name',
            //'client_address_line_1',
            //'client_address_line_2',
            //'subtotal_amount',
            //'coupon_id',
            //'coupon_code',
            //'discount_amount',
            //'cgst_percent',
            //'sgst_percent',
            //'igst_percent',
            //'total_payable',
            [
                'attribute' => 'status',
                'headerOptions' => ['class' => 'text-primary font-weight-bold', 'style' => 'width: 160px;'],
                'format' => 'raw',
                'value' => function ($model) {
                    $status = strtolower($model->status);
                    $badge = 'badge-secondary';
                    if ($status === 'paid') {
                        $badge = 'badge-success';
                    }
                    if ($status === 'unpaid') {
                        $badge = 'badge-danger';
                    }
                    if ($status === 'partially_paid') {
                        $badge = 'badge-warning';
                    }
                    if ($status === 'cancelled') {
                        $badge = 'badge-dark';
                    }
                    return '<span class="badge ' . $badge . ' px-3 py-2 text-uppercase d-block text-center">' . Html::encode(str_replace('_', ' ', $model->status)) . '</span>';
                },
                // DROPDOWN FILTER ACTIVATION: Replaces text box with absolute value array
                'filter' => [
                    'Unpaid' => 'Unpaid',
                    'Paid' => 'Paid',
                    'Partially_Paid' => 'Partially Paid',
                    'Cancelled' => 'Cancelled',
                    'Refunded' => 'Refunded'
                ],
            ],
            'due_date',
            [
                'attribute' => 'date_range',
                'label' => 'Created Timeline',
                'headerOptions' => ['class' => 'text-primary font-weight-bold', 'style' => 'width: 260px;'],
                'value' => function ($model) {
                    return date('d-M-Y h:i A', strtotime($model->created_at));
                },
                // 🔏 INSERT GRAPHICAL CALENDAR COMPONENT INTO DATAGRID FILTER ROW
                'filter' => \kartik\daterange\DateRangePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'date_range',
                    'convertFormat' => true,
                    'pluginOptions' => [
                        'locale' => ['format' => 'Y-m-d', 'separator' => ' - '],
                        'opens' => 'left'
                    ],
                    'options' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Select Date Spread...']
                ]),
            ],
            //'updated_at',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Invoice $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]);
    ?>


</div>
