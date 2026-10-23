<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Maintenance;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class CheckoutController extends Controller
{
    
    public $layout = 'account';
    
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'renew' => ['POST'],
                ],
            ],
        ];
    }

    public function actionRenew(
        string $type,
        int $id
    ) {
        $userId =
            (int) Yii::$app->user->id;

        $itemType =
            $this->resolveOwnedAssetType(
                $type,
                $id,
                $userId
            );

        $invoice = Invoice::find()
            ->alias('i')
            ->innerJoin(
                ['ii' => InvoiceItem::tableName()],
                'ii.invoice_id = i.id'
            )
            ->where([
                'i.user_id' => $userId,
                'ii.item_type' => $itemType,
                'ii.item_id' => $id,
                'i.status' => [
                    Invoice::STATUS_UNPAID,
                    Invoice::STATUS_PARTIALLY_PAID,
                ],
            ])
            ->orderBy([
                'i.created_at' => SORT_DESC,
                'i.id' => SORT_DESC,
            ])
            ->one();

        if ($invoice === null) {
            Yii::$app->session->setFlash(
                'warning',
                'No payable renewal invoice is currently available '
                . 'for this service.'
            );

            return $this->redirect([
                '/dashboard/index',
            ]);
        }

        return $this->redirect([
            '/invoice/view',
            'number' =>
                $invoice->invoice_number,
        ]);
    }

    private function resolveOwnedAssetType(
        string $type,
        int $id,
        int $userId
    ): int {
        $asset = match ($type) {
            'domain' =>
                Domains::findOne([
                    'id' => $id,
                    'user_id' => $userId,
                ]),

            'hosting' =>
                Hosting::findOne([
                    'id' => $id,
                    'user_id' => $userId,
                ]),

            'maintenance' =>
                Maintenance::findOne([
                    'id' => $id,
                    'user_id' => $userId,
                ]),

            default =>
                null,
        };

        if ($asset === null) {
            throw new NotFoundHttpException(
                'Requested service was not found.'
            );
        }

        return match ($type) {
            'domain' =>
                InvoiceItem::TYPE_DOMAIN,

            'hosting' =>
                InvoiceItem::TYPE_HOSTING,

            'maintenance' =>
                InvoiceItem::TYPE_MAINTENANCE,

            default =>
                throw new NotFoundHttpException(
                    'Unsupported service type.'
                ),
        };
    }
}