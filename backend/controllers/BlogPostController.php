<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\BlogPost;
use common\services\BlogImageService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/** Posts for the public blog at /blog. */
class BlogPostController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => BlogPost::find()->with('category')->orderBy(['status' => SORT_ASC, 'published_at' => SORT_DESC, 'id' => SORT_DESC]),
            ]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        return $this->saveForm(new BlogPost(['status' => BlogPost::STATUS_DRAFT, 'author_id' => Yii::$app->user->id]));
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

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Post deleted.');

        return $this->redirect(['index']);
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
        $wasPublic = $model->isPublic();
        if ($model->load(Yii::$app->request->post())) {
            $cover = UploadedFile::getInstance($model, 'cover_image');
            try {
                if ($cover !== null) {
                    $model->cover_image = (new BlogImageService())->store($cover);
                } elseif (Yii::$app->request->post('remove_cover')) {
                    $model->cover_image = null;
                }
                if ($model->save()) {
                    // Google finds new posts through the sitemap within days; asking it directly takes about a day.
                    $indexing = 'Ask Google to index it: click Search Console above, then URL inspection > paste ' . $this->publicUrl('blog/' . $model->slug) . ' > Request indexing.';
                    Yii::$app->session->setFlash('success', match ($model->stateLabel()) {
                        'Published' => $wasPublic ? 'Post saved.' : 'Post published. ' . $indexing,
                        'Scheduled' => 'Post saved. It goes live on ' . Yii::$app->formatter->asDatetime($model->published_at, 'php:d M Y, H:i') . '. Once it is live: ' . lcfirst($indexing),
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
