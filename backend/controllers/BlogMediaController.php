<?php

declare(strict_types=1);

namespace backend\controllers;

use common\services\BlogImageService;
use common\services\BlogMediaService;
use Yii;
use yii\data\ArrayDataProvider;
use yii\web\Response;
use yii\web\UploadedFile;

/** The blog's images: see them all, copy an address into a post, upload, and delete those no post uses. */
class BlogMediaController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ArrayDataProvider(['allModels' => BlogMediaService::all(), 'pagination' => ['pageSize' => 48]]),
            'base' => rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/',
        ]);
    }

    public function actionUpload(): Response
    {
        $file = UploadedFile::getInstanceByName('image');
        try {
            if ($file === null) {
                throw new \DomainException('Choose an image to upload.');
            }
            $path = (new BlogImageService())->store($file);
            Yii::$app->session->setFlash('success', 'Image uploaded. Copy its address to use it in a post: ' . rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/' . $path);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->session->setFlash('error', 'That did not work: the server could not write or remove the file. Please try again.');
        }

        return $this->redirect(['index']);
    }

    public function actionDelete(): Response
    {
        try {
            BlogMediaService::delete((string) Yii::$app->request->post('path', ''));
            Yii::$app->session->setFlash('success', 'Image deleted.');
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->session->setFlash('error', 'That did not work: the server could not write or remove the file. Please try again.');
        }

        return $this->redirect(['index']);
    }
}
