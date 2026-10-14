<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use backend\models\searches\LeadSearch;
use common\models\Lead;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * LeadController manages incoming customer infrastructure audit requirements records.
 */
class LeadController extends BaseController
{

    /**
     * Lists all corporate audit request records.
     */
    public function actionIndex()
    {
        $searchModel = new LeadSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Lead model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Lead();
        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }
        return $this->render('create', [
                    'model' => $model,
        ]);
    }

    /**
     * Displays a single infrastructure record configuration block details panel.
     */
    public function actionView($id)
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
        // Deny managers from editing lead statuses directly
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
     * Deletes an existing Lead model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Lead model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Lead the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): Lead
    {
        if (($model = Lead::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
