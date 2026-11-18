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
    public function actionIndex(): Response
    {
        // Every public page of the site (found by itself, see SeoPage::pages()); login, signup and the like are left out.
        $urls = array_map(static fn(string $route): array => ['loc' => $route === 'site/index' ? Url::home(true) : Url::to(['/' . $route], true)], array_keys(\common\models\SeoPage::pages()));
        // A post kept out of search engines stays out of the sitemap too.
        $posts = BlogPost::findPublic()->andWhere(['noindex' => 0])->select(['id', 'slug', 'updated_at', 'category_id'])->asArray()->all();
        foreach (BlogCategory::find()->where(['id' => array_filter(array_column($posts, 'category_id'))])->all() as $category) {
            $urls[] = ['loc' => Url::to(['/blog/index', 'category' => $category->slug], true)];
        }
        $tags = \common\models\BlogTag::find()->where(['id' => (new \yii\db\Query())->select('tag_id')->from('{{%blog_post_tag}}')->where(['post_id' => array_column($posts, 'id')])])->all();
        foreach ($tags as $tag) {
            $urls[] = ['loc' => Url::to(['/blog/index', 'tag' => $tag->slug], true)];
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

    /** /indexnow-key.txt: the key IndexNow search engines (Bing) read to trust this site's change notices. */
    public function actionIndexnowKey(): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $key = \common\services\IndexNowService::key();
        if ($key === '') {
            throw new \yii\web\NotFoundHttpException();
        }
        $response->content = $key;

        return $response;
    }
}
