<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\BlogCategory;
use common\models\BlogPost;
use common\models\BlogTag;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** The public blog: /blog, /blog/category/{slug}, /blog/{slug} and the RSS feed at /blog/feed. */
class BlogController extends Controller
{
    public function actionIndex(?string $category = null, ?string $tag = null): string
    {
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
        // The newest featured post leads the unfiltered blog, and is not repeated in the list below it.
        /** @var BlogPost|null $featured */
        $featured = $current === null && $currentTag === null ? BlogPost::findPublic()->andWhere(['is_featured' => 1])->with('category')->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC])->one() : null;
        if ($featured !== null) {
            $query->andWhere(['<>', 'id', $featured->id]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 9]]),
            'categories' => BlogCategory::find()->where(['id' => BlogPost::findPublic()->select('category_id')])->orderBy(['name' => SORT_ASC])->all(),
            'current' => $current,
            'tag' => $currentTag,
            'featured' => $featured,
        ]);
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

        return $this->render('view', ['post' => $post, 'related' => $related]);
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
