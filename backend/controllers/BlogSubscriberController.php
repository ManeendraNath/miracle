<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\BlogSubscriber;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Newsletter subscribers: who gets new blog posts by email. */
class BlogSubscriberController extends BaseController
{
    public function actionIndex(?string $status = null, mixed $q = null): string
    {
        $filter = $status !== null && ctype_digit($status) && isset(BlogSubscriber::statuses()[(int) $status]) ? (int) $status : null;
        $search = is_string($q) ? trim($q) : '';
        $query = BlogSubscriber::find()->orderBy(['id' => SORT_DESC])
            ->andFilterWhere(['status' => $filter])
            ->andFilterWhere(['like', 'email', $search]);

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 50]]),
            'filter' => $filter,
            'search' => $search,
            'counts' => BlogSubscriber::find()->select(['COUNT(*)', 'status'])->groupBy('status')->indexBy('status')->column(),
        ]);
    }

    /** For a request to be forgotten: the address and its delivery records go for good. */
    public function actionDelete(int $id): Response
    {
        (BlogSubscriber::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.'))->delete();
        Yii::$app->session->setFlash('success', 'Subscriber deleted.');

        return $this->redirect(['index']);
    }
}
