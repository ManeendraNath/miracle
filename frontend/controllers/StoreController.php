<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\CustomerBillingProfile;
use common\models\Product;
use common\services\customer\IncompleteProfileReminderService;
use common\services\payment\PayPalService;
use common\services\StoreOrderService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** The public store: website themes and plugins, with live demos and licence prices. */
class StoreController extends Controller
{
    /** /store, or /store?technology=wordpress */
    public function actionIndex(string $technology = ''): string
    {
        if ($technology !== '' && !isset(Product::TECHNOLOGIES[$technology])) {
            throw new NotFoundHttpException('Page not found.');
        }
        $query = Product::findPublic()->with('images')->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
        if ($technology !== '') {
            $query->andWhere(['technology' => $technology]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 24]]),
            'technology' => $technology,
            // Only the technologies there is something for.
            'technologies' => array_intersect_key(Product::TECHNOLOGIES, array_flip(Product::findPublic()->select('technology')->distinct()->column())),
        ]);
    }

    public function actionView(string $slug): string
    {
        $model = Product::findPublic()->andWhere(['slug' => $slug])->with('images')->one()
            ?? throw new NotFoundHttpException('Page not found.');

        return $this->render('view', ['model' => $model]);
    }

    /**
     * Checkout: the order to confirm (GET), then its invoice to pay (POST). Sign in and billing details come first,
     * each sending the buyer back here once done.
     */
    public function actionBuy(string $slug, string $licence = 'single'): string|Response
    {
        /** @var Product $model */
        $model = Product::findPublic()->andWhere(['slug' => $slug])->one() ?? throw new NotFoundHttpException('Page not found.');
        if (!isset(Product::LICENCES[$licence]) || $model->price($licence, false) === null) {
            throw new NotFoundHttpException('Page not found.');
        }
        $here = ['/store/buy', 'slug' => $slug, 'licence' => $licence];
        $session = Yii::$app->session;
        if (Yii::$app->user->isGuest) {
            $session->set(self::RESUME, [$here, time()]);
            $session->setFlash('info', 'Please sign in, or create an account, to buy ' . $model->name . '.');

            return Yii::$app->user->loginRequired();
        }
        $userId = (int) Yii::$app->user->id;
        $profile = CustomerBillingProfile::findOne(['user_id' => $userId]);
        if (IncompleteProfileReminderService::missingFields($profile) !== []) {
            $session->set(self::RESUME, [$here, time()]);
            $session->setFlash('warning', 'Please complete your billing details: they go on your invoice. You will come back to your order after saving.');

            return $this->redirect(['/account/profile']);
        }
        $session->remove(self::RESUME);
        $usd = $profile !== null && $profile->isForeign();
        // Buyers abroad pay by PayPal only (UPI and Indian bank transfer are for India): no invoice they can't pay.
        $canPay = !$usd || (new PayPalService())->acceptsCustomerPayments();
        if (Yii::$app->request->isPost && $canPay) {
            try {
                $invoice = (new StoreOrderService())->buy($userId, $model, $licence);
            } catch (\DomainException $e) {
                $session->setFlash('error', $e->getMessage());

                return $this->redirect($here);
            }

            return $this->redirect(['/invoice/view', 'number' => $invoice->invoice_number]);
        }

        return $this->render('buy', ['model' => $model, 'licence' => $licence, 'usd' => $usd, 'canPay' => $canPay]);
    }

    /** Session key: the order to come back to after signing in or completing billing details, and when it was left. */
    public const RESUME = 'storeResume';

    /** The order to come back to, if left in the last half hour; forgotten either way. */
    public static function takeResume(): ?array
    {
        $resume = Yii::$app->session->remove(self::RESUME);

        return is_array($resume) && is_array($resume[0] ?? null) && (int) ($resume[1] ?? 0) > time() - 1800 ? $resume[0] : null;
    }
}
