<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\BlogCategory;
use common\models\BlogPost;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Categories for the public blog. */
class BlogCategoryController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => BlogCategory::find()->orderBy(['name' => SORT_ASC])]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        return $this->saveForm(new BlogCategory());
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->saveForm($this->findModel($id));
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        $posts = (int) BlogPost::find()->where(['category_id' => $model->id])->count();
        $model->delete();
        Yii::$app->session->setFlash('success', 'Category deleted.' . ($posts > 0 ? " Its {$posts} post(s) now have no category." : ''));

        return $this->redirect(['index']);
    }

    private function saveForm(BlogCategory $model): Response|string
    {
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category saved.');

            return $this->redirect(['index']);
        }

        return $this->render('form', ['model' => $model]);
    }

    private function findModel(int $id): BlogCategory
    {
        return BlogCategory::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
    }
}
