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

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs']['actions']['site'] = ['POST'];

        return $behaviors;
    }

    public function actionIndex(int $product = 0, string $state = ''): string
    {
        $query = ProductOrder::find()->alias('o')->innerJoinWith('invoice i')->with(['product', 'user', 'supportTicket', 'maintenance'])->orderBy(['o.id' => SORT_DESC]);
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

    /** Moves a software licence to another web address (the customer asked, e.g. in a ticket). */
    public function actionSite(int $id): \yii\web\Response
    {
        $order = ProductOrder::findOne($id) ?? throw new \yii\web\NotFoundHttpException('Order not found.');
        $site = \Yii::$app->request->post('site', '');
        $host = is_string($site) ? ProductOrder::hostOf($site) : null;
        if (!$order->product->isSoftware() || $order->licence_key === null || !$order->isPaid()) {
            \Yii::$app->session->setFlash('error', 'Only the licence of a paid software subscription can be moved.');
        } elseif ($host === null) {
            \Yii::$app->session->setFlash('error', 'Give a web address such as billing.example.com.');
        } else {
            // A check from the old address says nothing about the new copy.
            $order->updateAttributes(['licence_site' => $host, 'website_url' => 'https://' . $host, 'licence_checked_at' => null]);
            // Renewals name the address too; a renewal collected but not yet invoiced takes it at the next billing run.
            $order->maintenance?->updateAttributes(['website_url' => 'https://' . $host, 'updated_at' => time()]);
            \Yii::$app->session->setFlash('success', 'The licence now works on ' . $host . '. The copy there confirms it at its next daily check. A renewal invoice already issued keeps the old address in its wording.');
        }

        // Back to the same filtered page of the list (only ever this admin's own pages).
        $back = (string) \Yii::$app->request->referrer;

        return $this->redirect(str_starts_with($back, \Yii::$app->request->hostInfo . '/') ? $back : ['index', 'product' => (int) $order->product_id]);
    }
}
