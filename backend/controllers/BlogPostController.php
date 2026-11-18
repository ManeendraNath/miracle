<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\BlogPost;
use common\models\BlogPostRevision;
use common\services\BlogImageService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/** Posts for the public blog at /blog. */
class BlogPostController extends BaseController
{
    public function actionIndex(mixed $trash = null): string
    {
        $inTrash = $trash === '1';

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => BlogPost::find()->with('category')->where($inTrash ? ['not', ['deleted_at' => null]] : ['deleted_at' => null])
                    ->orderBy($inTrash ? ['deleted_at' => SORT_DESC] : ['status' => SORT_ASC, 'published_at' => SORT_DESC, 'id' => SORT_DESC]),
                'pagination' => ['pageSize' => 30],
            ]),
            'inTrash' => $inTrash,
            'trashCount' => (int) BlogPost::find()->where(['not', ['deleted_at' => null]])->count(),
        ]);
    }

    /** Views a day for the last 30 days, and what readers did with the post. */
    public function actionStats(int $id): string
    {
        $model = $this->findModel($id);
        $since = date('Y-m-d', strtotime('-29 days'));
        $days = (new \yii\db\Query())->select(['COUNT(*)', 'day'])->from('{{%blog_post_view}}')
            ->where(['post_id' => $model->id])->andWhere(['>=', 'day', $since])->groupBy('day')->indexBy('day')->column();

        return $this->render('stats', [
            'model' => $model,
            'days' => $days,
            'views' => \common\models\BlogPostView::counts([(int) $model->id])[(int) $model->id] ?? ['total' => 0, 'recent' => 0],
            'comments' => \common\models\BlogComment::find()->select(['COUNT(*)', 'status'])->where(['post_id' => $model->id, 'parent_id' => null])->groupBy('status')->indexBy('status')->column(),
            'emailed' => (int) $model->getBlogNewsletterDeliveries()->andWhere(['failed' => 0])->count(),
            'redirects' => $model->getBlogPostRedirects()->select('old_slug')->column(),
        ]);
    }

    /** A Draft copy to start from, opened for editing. */
    public function actionDuplicate(int $id): Response
    {
        $copy = $this->findModel($id)->duplicate((int) Yii::$app->user->id);
        Yii::$app->session->setFlash('success', 'A Draft copy was made. Change its title and web address, then publish it when ready.');

        return $this->redirect(['update', 'id' => $copy->id]);
    }

    public function actionUntrash(int $id): Response
    {
        $model = $this->findModel($id);
        $model->untrash();
        Yii::$app->session->setFlash('success', $model->isPublic() ? 'Post restored: it is back on the website and in the sitemap.' : 'Post restored.');

        return $this->redirect(['index']);
    }

    /** Several posts at once, from the ticked boxes of the list. */
    public function actionBulk(): Response
    {
        $request = Yii::$app->request;
        $ids = array_map('intval', array_filter((array) $request->post('selection', []), 'is_numeric'));
        $action = (string) $request->post('bulk_action', '');
        $inTrash = $action === 'untrash' || $action === 'purge';
        $posts = $ids === [] ? [] : BlogPost::find()->where(['id' => $ids])->andWhere($inTrash ? ['not', ['deleted_at' => null]] : ['deleted_at' => null])->all();
        if ($posts === [] || !in_array($action, ['publish', 'draft', 'trash', 'untrash', 'purge'], true)) {
            Yii::$app->session->setFlash('warning', 'Tick at least one post and choose what to do with them.');

            return $this->redirect(['index', 'trash' => $inTrash ? '1' : null]);
        }
        foreach ($posts as $post) {
            $wasPublic = $post->isPublic();
            match ($action) {
                'publish' => $post->updateAttributes(['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => $post->published_at ?: time(), 'updated_at' => time()]),
                'draft' => $post->updateAttributes(['status' => BlogPost::STATUS_DRAFT, 'updated_at' => time()]),
                'trash' => $post->trash(),
                'untrash' => $post->untrash(),
                'purge' => $post->delete(),
            };
            if (in_array($action, ['publish', 'draft'], true) && ($wasPublic || $post->isPublic())) {
                \common\services\IndexNowService::queue('/blog/' . $post->slug);
            }
        }
        $n = count($posts) . ' post' . (count($posts) === 1 ? '' : 's');
        Yii::$app->session->setFlash('success', match ($action) {
            'publish' => "$n published (scheduled ones go live at their time). The sitemap shows them by itself.",
            'draft' => "$n set to Draft: off the website and out of the sitemap.",
            'trash' => "$n moved to the trash. Restore them from the Trash tab within " . BlogPost::TRASH_DAYS . ' days.',
            'untrash' => "$n restored.",
            'purge' => "$n deleted for good.",
        });

        return $this->redirect(['index', 'trash' => $inTrash ? '1' : null]);
    }

    public function actionCreate(): Response|string
    {
        // The database defaults (comments on, emailed to subscribers) tick their boxes on a new post.
        $post = (new BlogPost())->loadDefaultValues();
        $post->status = BlogPost::STATUS_DRAFT;
        $post->author_id = Yii::$app->user->id;

        return $this->saveForm($post);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->saveForm($this->findModel($id));
    }

    /** The post as visitors will see it, for drafts and scheduled posts too. */
    public function actionPreview(int $id): string
    {
        return $this->render('preview', ['model' => $this->findModel($id)]);
    }

    /** An earlier version, read-only, to compare before restoring it. */
    public function actionRevision(int $id): string
    {
        $revision = BlogPostRevision::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');

        return $this->render('revision', ['revision' => $revision, 'model' => $this->findModel((int) $revision->post_id)]);
    }

    /** Puts an earlier version's title, summary and text back; what it replaces is kept as a version too. */
    public function actionRestore(int $id): Response
    {
        $revision = BlogPostRevision::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
        $model = $this->findModel((int) $revision->post_id);
        $model->setAttributes(['title' => $revision->title, 'summary' => $revision->summary, 'body' => $revision->body], false);
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'The version of ' . Yii::$app->formatter->asDatetime($revision->created_at, 'php:d M Y, H:i') . ' was restored. The text it replaced is in the version list.');
        } else {
            Yii::$app->session->setFlash('error', 'The version could not be restored: ' . implode(' ', $model->getFirstErrors()));
        }

        return $this->redirect(['update', 'id' => $model->id]);
    }

    /** From the list: to the trash first; a post already there is deleted for good. */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        if ($model->deleted_at === null) {
            $model->trash();
            Yii::$app->session->setFlash('success', 'Post moved to the trash: it is off the website and out of the sitemap. Restore it from the Trash tab within ' . BlogPost::TRASH_DAYS . ' days.');

            return $this->redirect(['index']);
        }
        $model->delete();
        Yii::$app->session->setFlash('success', 'Post deleted for good.');

        return $this->redirect(['index', 'trash' => '1']);
    }

    /** The editor's image button: stores the image and returns its public address. */
    public function actionUploadImage(): Response
    {
        $file = UploadedFile::getInstanceByName('image');
        try {
            if ($file === null) {
                throw new \DomainException('Choose an image to upload.');
            }
            $path = (new BlogImageService())->store($file);

            return $this->asJson(['url' => $this->publicUrl($path)]);
        } catch (\DomainException $e) {
            Yii::$app->response->statusCode = 422;

            return $this->asJson(['error' => $e->getMessage()]);
        }
    }

    private function saveForm(BlogPost $model): Response|string
    {
        $wasLive = $model->isPublic();
        $wasPublic = $wasLive && !$model->noindex;
        $oldSlug = $model->slug;
        if (!$model->getIsNewRecord()) {
            $model->tagNames = $model->tagNamesText();
        }
        if ($model->load(Yii::$app->request->post())) {
            $cover = UploadedFile::getInstance($model, 'cover_image');
            try {
                $stored = null;
                if ($cover !== null) {
                    $model->cover_image = $stored = (new BlogImageService())->store($cover);
                } elseif (Yii::$app->request->post('remove_cover')) {
                    $model->cover_image = null;
                }
                try {
                    $saved = $model->save();
                } catch (\Throwable $e) {
                    if ($stored !== null) {
                        @unlink(Yii::getAlias('@frontend/web/' . $stored));
                    }
                    throw $e;
                }
                if (!$saved) {
                    // The form shows the cover as saved; a removal stays ticked (the view reads remove_cover).
                    $model->cover_image = $model->getOldAttribute('cover_image');
                    if ($stored !== null) {
                        // The form can't carry the new file back: drop it and ask for it again, rather than lose it silently.
                        @unlink(Yii::getAlias('@frontend/web/' . $stored));
                        $model->addError('cover_image', 'Fix the errors above, then choose the cover image again.');
                    }
                }
                if ($saved) {
                    // /sitemap.xml is built on every request, so it is always current, and Google re-reads it by itself:
                    // nothing ever needs resubmitting. Asking Google to index a new address directly is faster (about a day).
                    $indexing = 'To have it in Google sooner: click Search Console above, then URL inspection > paste ' . $this->publicUrl('blog/' . $model->slug) . ' > Request indexing.';
                    $listed = $model->isPublic() && !$model->noindex;
                    Yii::$app->session->setFlash('success', match (true) {
                        $model->deleted_at !== null => 'Post saved. It is in the trash, so it is not on the website: restore it to show it again.',
                        $model->stateLabel() === 'Scheduled' => 'Post saved. ' . ($wasLive ? 'It is off the website until ' : 'It goes live on ')
                            . Yii::$app->formatter->asDatetime($model->published_at, 'php:d M Y, H:i')
                            . ($model->noindex ? ', kept out of search engines and the sitemap.' : ' and joins the sitemap then by itself. Once it is live: ' . lcfirst($indexing)),
                        $listed && $wasLive && !$wasPublic => 'Post saved and added to the sitemap automatically; Google reads the sitemap by itself, so there is nothing to resubmit. ' . $indexing,
                        $listed && !$wasPublic => 'Post published and added to the sitemap automatically; Google reads the sitemap by itself, so there is nothing to resubmit. ' . $indexing,
                        $listed && $model->slug !== $oldSlug => 'Post saved. Its web address changed: the sitemap now lists the new one by itself, and the old address sends readers and search engines to it. ' . $indexing,
                        $listed => 'Post saved. The sitemap shows the update by itself; nothing to do in Search Console.',
                        $wasPublic && $model->stateLabel() === 'Draft' => 'Draft saved: visitors can\'t see it until you publish it, and it was taken out of the sitemap automatically. Nothing to do in Search Console.',
                        $wasPublic => 'Post saved and taken out of the sitemap automatically. Google drops it from search on its next visit; nothing to do in Search Console.',
                        $model->isPublic() => 'Post saved. It is on the website but kept out of search engines and the sitemap.',
                        default => 'Draft saved. Visitors can\'t see it until you publish it.',
                    });

                    return $this->redirect(['update', 'id' => $model->id]);
                }
            } catch (\DomainException $e) {
                $model->addError('cover_image', $e->getMessage());
            }
        }

        return $this->render('form', ['model' => $model]);
    }

    /** An image path under the frontend web root as a full address on the public site. */
    private function publicUrl(string $path): string
    {
        return rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/' . $path;
    }

    private function findModel(int $id): BlogPost
    {
        return BlogPost::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
    }
}
