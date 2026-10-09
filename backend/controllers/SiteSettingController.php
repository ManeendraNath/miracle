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
     * @return string
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

}
