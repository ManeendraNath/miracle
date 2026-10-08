<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use common\models\AuditRequest;
use common\models\AuditRequestSearch;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * LeadController manages incoming customer infrastructure audit requirements records.
 */
class LeadController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Must be logged in as an administrator
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all corporate audit request records.
     */
    public function actionIndex()
    {
        $searchModel = new AuditRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single infrastructure record configuration block details panel.
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Updates an incoming lead status parameters array.
     */
    public function actionUpdate(int $id)
    {
        $user = Yii::$app->user->identity;
        // 🔒 RBAC SHIELD: Deny managers from editing lead statuses directly
        if ($user === null || (!$user->isSuperAdmin() && !$user->isAdmin())) {
            throw new ForbiddenHttpException('Access Denied: Your staff tier lacks capability modifications privileges.');
        }

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Lead configuration pipeline state updated.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the AuditRequest model based on its primary key value.
     */
    protected function findModel(int $id): AuditRequest
    {
        if (($model = AuditRequest::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested inquiry index data cluster does not exist.');
    }
}
