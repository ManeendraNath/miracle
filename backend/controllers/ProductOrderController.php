<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Invoice;
use common\models\Product;
use common\models\ProductOrder;
use yii\data\ActiveDataProvider;

/** Store sales: every order of a theme or plugin licence, with its invoice, licence key and first download. */
class ProductOrderController extends BaseController
{
    /** As ProductOrder::state(), with "checking" counted as due. */
    public const STATES = ['paid' => 'Paid', 'due' => 'Awaiting payment', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled'];

    public function actionIndex(int $product = 0, string $state = ''): string
    {
        $query = ProductOrder::find()->alias('o')->innerJoinWith('invoice i')->with(['product', 'user', 'supportTicket'])->orderBy(['o.id' => SORT_DESC]);
        if ($product > 0) {
            $query->andWhere(['o.product_id' => $product]);
        }
        $open = [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID];
        match ($state) {
            'paid' => $query->andWhere(['not', ['o.paid_at' => null]])->andWhere(['i.status' => Invoice::STATUS_PAID]),
            'due' => $query->andWhere(['o.paid_at' => null, 'i.status' => $open]),
            // Paid once, its money since given back (the invoice reopened, or closed as refunded).
            'refunded' => $query->andWhere(['or', ['and', ['not', ['o.paid_at' => null]], ['not', ['i.status' => Invoice::STATUS_PAID]]], ['i.status' => Invoice::STATUS_REFUNDED]]),
            'cancelled' => $query->andWhere(['o.paid_at' => null])->andWhere(['not in', 'i.status', [...$open, Invoice::STATUS_PAID, Invoice::STATUS_REFUNDED]]),
            default => null,
        };

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 30], 'sort' => false]),
            'products' => Product::find()->select('name')->indexBy('id')->orderBy(['name' => SORT_ASC])->column(),
            'product' => $product,
            'state' => isset(self::STATES[$state]) ? $state : '',
        ]);
    }
}
