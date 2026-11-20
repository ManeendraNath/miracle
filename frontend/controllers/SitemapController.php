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
        $posts = BlogPost::findPublic()->andWhere(['noindex' => 0])->select(['id', 'slug', 'updated_at', 'published_at', 'category_id'])->asArray()->all();
        // A list changes when its newest item does: that date, not a guess (search engines ignore made-up ones). A post
        // counts from when it went public, as a scheduled one appears after its last edit. Removals are not tracked.
        $newest = static fn(array $rows): ?string => $rows === [] ? null : date('Y-m-d', max(array_map(static fn(array $row): int => max((int) $row['updated_at'], (int) ($row['published_at'] ?? 0)), $rows)));
        // Lists show noindex posts too, so their dates count for the lists.
        $listed = BlogPost::findPublic()->select(['updated_at', 'published_at', 'category_id'])->asArray()->all();
        $dated = ['blog/index' => $newest($listed)];
        foreach (['store/index' => 'store', 'store/services' => 'services', 'store/plans' => 'plans'] as $route => $section) {
            $dated[$route] = $newest(\common\models\Product::findPublicOf($section)->select('updated_at')->asArray()->all());
        }
        // An empty store section (no date: nothing in it) is not a page worth listing.
        foreach (['store/index', 'store/services', 'store/plans'] as $route) {
            if ($dated[$route] === null) {
                $urls = array_values(array_filter($urls, static fn(array $u): bool => $u['loc'] !== Url::to(['/' . $route], true)));
            }
        }
        foreach ($urls as &$url) {
            foreach ($dated as $route => $date) {
                if ($date !== null && $url['loc'] === Url::to(['/' . $route], true)) {
                    $url['lastmod'] = $date;
                }
            }
        }
        unset($url);
        foreach (BlogCategory::find()->where(['id' => array_filter(array_column($posts, 'category_id'))])->all() as $category) {
            $inCategory = array_filter($listed, static fn(array $post): bool => (int) $post['category_id'] === (int) $category->id);
            $urls[] = array_filter(['loc' => Url::to(['/blog/index', 'category' => $category->slug], true), 'lastmod' => $newest($inCategory)]);
        }
        $tags = \common\models\BlogTag::find()->where(['id' => (new \yii\db\Query())->select('tag_id')->from('{{%blog_post_tag}}')->where(['post_id' => array_column($posts, 'id')])])->all();
        foreach ($tags as $tag) {
            $urls[] = ['loc' => Url::to(['/blog/index', 'tag' => $tag->slug], true)];
        }
        foreach (\common\models\Product::findPublic()->select(['slug', 'updated_at'])->asArray()->all() as $product) {
            $urls[] = ['loc' => Url::to(['/store/view', 'slug' => $product['slug']], true), 'lastmod' => date('Y-m-d', (int) $product['updated_at'])];
        }
        foreach ($posts as $post) {
            $urls[] = ['loc' => Url::to(['/blog/view', 'slug' => $post['slug']], true), 'lastmod' => $newest([$post])];
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
