<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\BlogCategory;
use common\models\BlogPost;
use common\models\BlogTag;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** The public blog: /blog, /blog/category/{slug}, /blog/{slug} and the RSS feed at /blog/feed. */
class BlogController extends Controller
{
    public function actionIndex(?string $category = null, ?string $tag = null, ?string $year = null, ?string $month = null, mixed $q = null): string
    {
        $search = is_string($q) ? mb_substr(trim($q), 0, 100) : '';
        $start = $year !== null && $month !== null && preg_match('/^\d{4}$/', $year) === 1 && preg_match('/^(0[1-9]|1[0-2])$/', $month) === 1
            ? (int) strtotime("$year-$month-01") : null;
        if (($year !== null || $month !== null) && $start === null) {
            throw new NotFoundHttpException('This month does not exist.');
        }
        $current = $category === null ? null : (BlogCategory::findOne(['slug' => $category]) ?? throw new NotFoundHttpException('This category does not exist.'));
        // Only a tag on a live post has a page: not one used only on drafts, nor one no post uses any more.
        $currentTag = $tag === null ? null : (BlogTag::find()->where(['slug' => $tag])
            ->andWhere(['id' => (new Query())->select('tag_id')->from('{{%blog_post_tag}}')->where(['post_id' => BlogPost::findPublic()->select('id')])])
            ->one() ?? throw new NotFoundHttpException('This tag does not exist.'));
        $query = BlogPost::findPublic()->with('category')->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC]);
        if ($current !== null) {
            $query->andWhere(['category_id' => $current->id]);
        }
        if ($currentTag !== null) {
            $query->andWhere(['id' => (new Query())->select('post_id')->from('{{%blog_post_tag}}')->where(['tag_id' => $currentTag->id])]);
        }
        if ($start !== null) {
            $query->andWhere(['>=', 'published_at', $start])->andWhere(['<', 'published_at', strtotime('+1 month', $start)]);
        }
        if ($search !== '') {
            // The words of the text, not its markup ("strong", "href"). ponytail: LIKE over every post is fine for hundreds
            // of posts; a FULLTEXT index on a plain-text column once there are thousands.
            $query->andWhere(['or', ['like', 'title', $search], ['like', 'summary', $search], ['like', new Expression("REGEXP_REPLACE(body, '<[^>]*>', ' ')"), $search]]);
        }
        $filtered = $current !== null || $currentTag !== null || $start !== null || $search !== '';
        // The newest featured post leads the unfiltered blog, and is not repeated in the list below it.
        /** @var BlogPost|null $featured */
        $featured = !$filtered ? BlogPost::findPublic()->andWhere(['is_featured' => 1])->with('category')->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC])->one() : null;
        if ($featured !== null) {
            $query->andWhere(['<>', 'id', $featured->id]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 9]]),
            'categories' => BlogCategory::find()->where(['id' => BlogPost::findPublic()->select('category_id')])->orderBy(['name' => SORT_ASC])->all(),
            'current' => $current,
            'tag' => $currentTag,
            'featured' => $featured,
            'month' => $start,
            'search' => $search,
            // Months with posts, newest first: "2026-11" => 3.
            'archive' => array_count_values(array_map(static fn($at): string => date('Y-m', (int) $at), BlogPost::findPublic()->select('published_at')->orderBy(['published_at' => SORT_DESC])->column())),
            'popular' => $filtered ? [] : $this->popular(),
        ]);
    }

    /**
     * The most-read posts of the last 30 days.
     *
     * @return BlogPost[]
     */
    private function popular(): array
    {
        $ids = (new Query())->select('post_id')->from('{{%blog_post_view}}')
            ->where(['>=', 'day', date('Y-m-d', strtotime('-29 days'))])->andWhere(['post_id' => BlogPost::findPublic()->select('id')])
            ->groupBy('post_id')->orderBy(['COUNT(*)' => SORT_DESC, 'post_id' => SORT_DESC])->limit(5)->column();
        $posts = BlogPost::find()->where(['id' => $ids])->indexBy('id')->all();

        return array_values(array_filter(array_map(static fn($id) => $posts[$id] ?? null, $ids)));
    }

    public function actionView(string $slug): string
    {
        $post = BlogPost::findPublic()->andWhere(['slug' => $slug])->with(['category', 'tags', 'author'])->one();
        if (!$post instanceof BlogPost) {
            throw new NotFoundHttpException('This post does not exist.');
        }
        $related = BlogPost::findPublic()->andWhere(['<>', 'id', $post->id])
            ->orderBy($post->category_id ? [new \yii\db\Expression('category_id = :category DESC', [':category' => $post->category_id]), 'published_at' => SORT_DESC] : ['published_at' => SORT_DESC])
            ->limit(3)->all();
        \common\models\BlogPostView::record($post, Yii::$app->request);
        $older = BlogPost::findPublic()->andWhere(['or', ['<', 'published_at', $post->published_at], ['and', ['published_at' => $post->published_at], ['<', 'id', $post->id]]])
            ->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC])->one();
        $newer = BlogPost::findPublic()->andWhere(['or', ['>', 'published_at', $post->published_at], ['and', ['published_at' => $post->published_at], ['>', 'id', $post->id]]])
            ->orderBy(['published_at' => SORT_ASC, 'id' => SORT_ASC])->one();

        return $this->render('view', ['post' => $post, 'related' => $related, 'older' => $older, 'newer' => $newer]);
    }

    /** RSS 2.0 of the 20 newest posts. */
    public function actionFeed(): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->content = $this->renderPartial('feed', ['posts' => BlogPost::findPublic()->orderBy(['published_at' => SORT_DESC])->limit(20)->all()]);

        return $response;
    }
}
