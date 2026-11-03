<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\User;
use common\models\UserLoginHistory;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

final class SecurityController extends Controller
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
                'actions' => ['sign-out-other-devices' => ['POST']],
            ],
        ];
    }

    /**
     * A new auth key invalidates every other session and remember-me cookie (yii\web\User checks it
     * on each request); this browser is switched to the new key so it stays signed in.
     */
    public function actionSignOutOtherDevices()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->generateAuthKey();
        $user->updateAttributes(['auth_key' => $user->auth_key, 'updated_at' => time()]);
        $remembered = Yii::$app->request->cookies->has(Yii::$app->user->identityCookie['name']);
        Yii::$app->user->switchIdentity($user, $remembered ? 3600 * 24 * 30 : 0);
        Yii::$app->session->setFlash('success', 'You have been signed out of all other devices.');
        return $this->redirect(['index']);
    }

    public function actionIndex()
    {
        $query = UserLoginHistory::find()
            ->where([
                'user_id' => (int) Yii::$app->user->id,
            ])
            ->orderBy([
                'logged_in_at' => SORT_DESC,
                'id' => SORT_DESC,
            ]);

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]),
        ]);
    }
}
