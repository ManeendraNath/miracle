<?php

namespace backend\controllers;

use common\models\Coupons;
use common\models\CouponsSearch;
use yii\web\NotFoundHttpException;

/**
 * CouponsController implements the CRUD actions for Coupons model.
 */
class CouponsController extends BaseController
{

    /**
     * Lists all Coupons models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CouponsSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Coupons model.
     * @param int $coupon_id Coupon ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($coupon_id)
    {
        return $this->render('view', [
            'model' => $this->findModel($coupon_id),
        ]);
    }

    /**
     * Creates a new Coupons model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Coupons();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'coupon_id' => $model->coupon_id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Coupons model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $coupon_id Coupon ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($coupon_id)
    {
        $model = $this->findModel($coupon_id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'coupon_id' => $model->coupon_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Coupons model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $coupon_id Coupon ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($coupon_id)
    {
        $this->findModel($coupon_id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Coupons model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $coupon_id Coupon ID
     * @return Coupons the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($coupon_id)
    {
        if (($model = Coupons::findOne(['coupon_id' => $coupon_id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
