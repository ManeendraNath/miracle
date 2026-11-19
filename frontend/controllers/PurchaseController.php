<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\ProductOrder;
use common\services\StoreOrderService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** My purchases: store orders with their licence keys and downloads. */
final class PurchaseController extends Controller
{
    public $layout = 'account';

    #[\Override]
    public function behaviors(): array
    {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['cancel' => ['POST']]],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'orders' => ProductOrder::find()->where(['user_id' => (int) Yii::$app->user->id])->with(['product', 'invoice', 'supportTicket', 'maintenance'])->orderBy(['id' => SORT_DESC])->all(),
        ]);
    }

    public function actionDownload(int $id): Response
    {
        $order = $this->findOwned($id);
        $path = $order->product->downloadPath();
        if (!$order->licenceActive() || !$order->product->hasLicence() || $path === null || !is_file($path)) {
            throw new NotFoundHttpException('This download is not available.');
        }

        // The first download ends the full-refund period (site/licence).
        if ($order->first_downloaded_at === null) {
            $order->updateAttributes(['first_downloaded_at' => time()]);
        }

        return Yii::$app->response->sendFile($path, $order->product->download_name ?: basename($path));
    }

    public function actionCancel(int $id): Response
    {
        try {
            (new StoreOrderService())->cancel($this->findOwned($id));
            Yii::$app->session->setFlash('success', 'Order cancelled.');
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    private function findOwned(int $id): ProductOrder
    {
        return ProductOrder::findOne(['id' => $id, 'user_id' => (int) Yii::$app->user->id]) ?? throw new NotFoundHttpException('Order not found.');
    }
}
