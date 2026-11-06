<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Testimonial;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Client quotes for the public home page. */
class TestimonialController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => Testimonial::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]),
            ]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        return $this->saveForm(new Testimonial());
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->saveForm($this->findModel($id));
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Testimonial deleted.');
        return $this->redirect(['index']);
    }

    private function saveForm(Testimonial $model): Response|string
    {
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Testimonial saved.');
            return $this->redirect(['index']);
        }
        return $this->render('form', ['model' => $model]);
    }

    private function findModel(int $id): Testimonial
    {
        return Testimonial::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
    }
}
