<?php

namespace backend\controllers;

use Yii;
use common\models\SiteSetting;

/**
 * SiteSettingController implements the CRUD actions for SiteSetting model.
 */
class SiteSettingController extends BaseController
{
    /**
     * Lists all SiteSetting models.
     *
     * @return string|\yii\web\Response
     */
    public function actionIndex()
    {
        $model = SiteSetting::findOne(1) ?? new SiteSetting();

        if ($model->load(Yii::$app->request->post())) {
            $model->updated_at = time(); // Automates tracking audit timestamps natively
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Global branding configurations committed successfully.');
                return $this->refresh();
            }
        }

        return $this->render('index', [
                    'model' => $model,
        ]);
    }

    /** Fetches the daily US dollar rate now, instead of waiting out the 12 hours since the last try. */
    public function actionFetchRate(): \yii\web\Response
    {
        $settings = \common\components\CustomYii::getSetting();
        $settings->setAttributes(['usd_rate_checked_at' => 0], false);
        (new \common\services\payment\ExchangeRateService())->usdRate();
        $fresh = (int) $settings->usd_rate_fetched_at >= time() - 60;
        Yii::$app->session->setFlash($fresh ? 'success' : 'error', $fresh
            ? 'Exchange rate updated: ₹' . $settings->usd_market_rate . ' per US$1.'
            : 'The exchange rate could not be fetched. The reason is in the admin log (backend/runtime/logs/app.log, "Exchange rate"). You can enter a rate by hand meanwhile.');

        return $this->redirect(['index']);
    }
}
