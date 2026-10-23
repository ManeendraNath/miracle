<?php

namespace backend\controllers;

use Yii;
use backend\models\searches\UserSearch;
use common\models\User;

class UserController extends BaseController
{

    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * ⚡ SECURE ONE-CLICK USER ACTIVATION TOGGLE
     * Switches the profile status between Active (10) and Inactive (0) dynamically.
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested client profile account does not exist.');
        }

        // Standard Yii2 Advanced template state statuses (Active = 1, Inactive = 0)
        if ((int) $model->status === User::getStatusActive()) {
            $model->status = User::getStatusInactive();
            Yii::$app->session->setFlash('warning', "Account for profile '{$model->username}' has been suspended successfully.");
        } else {
            $model->status = User::getStatusActive();
            Yii::$app->session->setFlash('success', "Account for profile '{$model->username}' has been activated and unlocked successfully!");
        }

        $model->save(false); // Bypasses password validation restrictions during simple status toggles
        return $this->redirect(['index']);
    }

    /**
     * Displays a single User model.
     * @param int $id System ID Reference
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'User created successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id System ID Reference
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->clearPlainPassword();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'User updated successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id System ID Reference
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Client profile deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id System ID Reference
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
