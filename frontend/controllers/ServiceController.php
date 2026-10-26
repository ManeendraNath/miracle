<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\BillingEvent;
use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Maintenance;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class ServiceController extends Controller
{

    public $layout = 'account';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' =>
                AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $userId = (int) Yii::$app->user->id;
        $domains = Domains::find()
                ->where(['user_id' => $userId])
                ->orderBy(['current_expiry_date' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        $hostings = Hosting::find()
                ->where(['user_id' => $userId])
                ->orderBy(['current_expiry_date' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        $maintenance = Maintenance::find()
                ->where(['user_id' => $userId])
                ->orderBy(['current_expiry_date' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        return $this->render('index', ['domains' => $domains, 'hosting' => $hostings, 'maintenance' => $maintenance]);
    }

    public function actionView(string $type, int $id)
    {
        $userId = (int) Yii::$app->user->id;

        [$model, $itemType] = match ($type) {
            'domain' => [
                Domains::findOne(['id' => $id, 'user_id' => $userId]),
                InvoiceItem::TYPE_DOMAIN,
            ],
            'hosting' => [
                Hosting::findOne(['id' => $id, 'user_id' => $userId]),
                InvoiceItem::TYPE_HOSTING,
            ],
            'maintenance' => [
                Maintenance::findOne(['id' => $id, 'user_id' => $userId]),
                InvoiceItem::TYPE_MAINTENANCE,
            ],
            default => [null, null],
        };

        if ($model === null || $itemType === null) {
            throw new NotFoundHttpException('Service does not exist.');
        }

        $latestEvent = BillingEvent::find()
                ->where(['user_id' => $userId, 'item_type' => $itemType, 'item_id' => $id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
        $invoice = Invoice::find()
                ->alias('i')
                ->innerJoin(['ii' => InvoiceItem::tableName()], 'ii.invoice_id = i.id')
                ->where(['i.user_id' => $userId, 'ii.item_type' => $itemType, 'ii.item_id' => $id])
                ->orderBy(['i.id' => SORT_DESC])
                ->one();
        $billingEvents = BillingEvent::find()
                ->where(['user_id' => $userId, 'item_type' => $itemType, 'item_id' => $id])
                ->orderBy(['id' => SORT_DESC])
                ->limit(5)
                ->all();
        return $this->render('view', ['model' => $model, 'type' => $type, 'latestEvent' => $latestEvent, 'invoice' => $invoice, 'billingEvents' => $billingEvents]);
    }
    
}
