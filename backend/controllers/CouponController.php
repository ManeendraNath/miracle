<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\CouponSearch;
use common\models\Coupon;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class CouponController extends BaseController
{

    public function actionIndex(): string
    {
        $searchModel = new CouponSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(
            int $id
    ): string
    {
        return $this->render(
                        'view',
                        [
                            'model' =>
                            $this->findModel($id),
                        ]
                );
    }

    public function actionCreate(): Response|string
    {
        $model = new Coupon();
        $model->type = Coupon::TYPE_FIXED;
        $model->is_active = Coupon::ACTIVE_YES;
        $model->uses_per_coupon = 1;
        $model->uses_per_user = 0;
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Coupon created successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Coupon updated successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Coupon deleted successfully.');
        return $this->redirect(['index']);
    }

    private function findModel(int $couponId): Coupon
    {
        $model = Coupon::findOne(['id' => $couponId]);
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Coupon does not exist.');
    }
}
