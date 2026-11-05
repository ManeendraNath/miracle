<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\InvoiceItem;
use common\models\Maintenance;
use common\services\billing\InvoicePaymentService;
use Yii;
use yii\filters\AccessControl;
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
        ];
    }

    public function actionRenew(string $type, int $id)
    {
        $userId = (int) Yii::$app->user->id;

        $itemType = $this->resolveOwnedAssetType($type, $id, $userId);

        $invoice = (new InvoicePaymentService())->payableInvoiceForService($userId, $itemType, $id);

        if ($invoice === null) {
            $asset = ['domain' => Domains::class, 'hosting' => Hosting::class, 'maintenance' => Maintenance::class][$type]::findOne($id);
            Yii::$app->session->setFlash(
                'warning',
                (new InvoicePaymentService())->renewalState($asset, $itemType)['billable']
                    ? 'The renewal invoice for this service has not been issued yet. We email it to you before the expiry date.'
                    : 'This service is not renewed automatically. Please contact us to renew it.',
            );

            return $this->redirect(['/dashboard/index']);
        }

        return $this->redirect(['/invoice/view', 'number' => $invoice->invoice_number]);
    }

    private function resolveOwnedAssetType(string $type, int $id, int $userId): int
    {
        $asset = match ($type) {
            'domain' => Domains::findOne([
                'id' => $id,
                'user_id' => $userId,
            ]),

            'hosting' => Hosting::findOne([
                'id' => $id,
                'user_id' => $userId,
            ]),

            'maintenance' => Maintenance::findOne([
                'id' => $id,
                'user_id' => $userId,
            ]),

            default => null,
        };

        if ($asset === null) {
            throw new NotFoundHttpException('Requested service was not found.');
        }

        return match ($type) {
            'domain' => InvoiceItem::TYPE_DOMAIN,

            'hosting' => InvoiceItem::TYPE_HOSTING,

            'maintenance' => InvoiceItem::TYPE_MAINTENANCE,

            default => throw new NotFoundHttpException('Unsupported service type.'),
        };
    }
}
