<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Product;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/** The public store: website themes and plugins, with live demos and licence prices. */
class StoreController extends Controller
{
    /** /store, or /store?technology=wordpress */
    public function actionIndex(string $technology = ''): string
    {
        if ($technology !== '' && !isset(Product::TECHNOLOGIES[$technology])) {
            throw new NotFoundHttpException('Page not found.');
        }
        $query = Product::findPublic()->with('images')->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
        if ($technology !== '') {
            $query->andWhere(['technology' => $technology]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 24]]),
            'technology' => $technology,
            // Only the technologies there is something for.
            'technologies' => array_intersect_key(Product::TECHNOLOGIES, array_flip(Product::findPublic()->select('technology')->distinct()->column())),
        ]);
    }

    public function actionView(string $slug): string
    {
        $model = Product::findPublic()->andWhere(['slug' => $slug])->with('images')->one()
            ?? throw new NotFoundHttpException('Page not found.');

        return $this->render('view', ['model' => $model]);
    }
}
