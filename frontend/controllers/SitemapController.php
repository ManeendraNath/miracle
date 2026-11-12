<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\BlogCategory;
use common\models\BlogPost;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

/** /sitemap.xml for search engines: the public pages, the blog, its categories and every published post. */
class SitemapController extends Controller
{
    /** Public pages worth finding in search; login, signup and the like are left out. */
    private const PAGES = ['/site/index', '/site/about', '/site/audit', '/site/hosting', '/site/contact', '/blog/index', '/site/privacy', '/site/terms', '/site/refund-policy', '/site/shipping-policy'];

    public function actionIndex(): Response
    {
        $urls = array_map(static fn(string $route): array => ['loc' => $route === '/site/index' ? Url::home(true) : Url::to([$route], true)], self::PAGES);
        $posts = BlogPost::findPublic()->select(['slug', 'updated_at', 'category_id'])->asArray()->all();
        foreach (BlogCategory::find()->where(['id' => array_filter(array_column($posts, 'category_id'))])->all() as $category) {
            $urls[] = ['loc' => Url::to(['/blog/index', 'category' => $category->slug], true)];
        }
        foreach ($posts as $post) {
            $urls[] = ['loc' => Url::to(['/blog/view', 'slug' => $post['slug']], true), 'lastmod' => date('Y-m-d', (int) $post['updated_at'])];
        }
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->content = $this->renderPartial('index', ['urls' => $urls]);

        return $response;
    }
}
