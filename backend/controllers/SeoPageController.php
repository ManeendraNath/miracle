<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\SeoPage;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Titles and descriptions search engines show for the website's fixed pages. */
class SeoPageController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', ['saved' => SeoPage::find()->indexBy('route')->all()]);
    }

    public function actionUpdate(string $route): Response|string
    {
        if (!isset(SeoPage::pages()[$route])) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $model = SeoPage::findOne(['route' => $route]) ?? new SeoPage(['route' => $route]);
        $post = Yii::$app->request->post($model->formName());
        if (is_array($post)) {
            // Only these two come from the form: the page is fixed by the address.
            $model->setAttributes(array_intersect_key($post, ['title' => true, 'description' => true]));
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Saved. Search engines show it after they next visit the page, usually within days.');

                return $this->redirect(['index']);
            }
        }

        return $this->render('form', ['model' => $model]);
    }
}
