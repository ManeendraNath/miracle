<?php

namespace backend\controllers;

use Yii;
use common\models\SiteSetting;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
/**
 * SiteSettingController implements the CRUD actions for SiteSetting model.
 */
class SiteSettingController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        
        $behaviors = [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'update'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            // 🎭 ROLE CHECK GATE: Restricts page access to Superadmin and Admin tiers only
                            if ($user !== null && ($user->isSuperAdmin() || $user->isAdmin())) {
                                return true;
                            }
                            throw new ForbiddenHttpException('Access denied. You do not possess sufficient infrastructure role privileges.');
                        },
                    ],
                ],
            ],
        ];
        
        return \yii\helpers\ArrayHelper::merge(parent::behaviors(), $behaviors);
    }

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
