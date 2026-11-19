<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Product;
use common\models\ProductImage;
use common\models\ProductOrder;
use common\services\BlogImageService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/** The store's themes and plugins: details, prices, cover and screenshots. */
class ProductController extends BaseController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => Product::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]), 'pagination' => ['pageSize' => 30]]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Product();
        $model->loadDefaultValues();

        return $this->saveForm($model);
    }

    public function actionUpdate(int $id): Response|string
    {
        return $this->saveForm($this->findModel($id));
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        if (ProductOrder::find()->where(['product_id' => $model->id])->exists()) {
            Yii::$app->session->setFlash('error', 'This product has orders, so it cannot be deleted: set it to Draft to take it out of the store.');

            return $this->redirect(['update', 'id' => $model->id]);
        }
        $files = array_filter([$model->cover_image, ...array_map(static fn(ProductImage $i): string => $i->path, $model->images)]);
        $model->delete();
        foreach ($files as $file) {
            @unlink(Yii::getAlias('@frontend/web/' . $file));
        }
        if ($model->downloadPath() !== null) {
            @unlink($model->downloadPath());
        }
        Yii::$app->session->setFlash('success', 'Product deleted, with its images and download file.');

        return $this->redirect(['index']);
    }

    private function saveForm(Product $model): Response|string
    {
        if (!$model->load(Yii::$app->request->post())) {
            // A file over the server's post limit empties the whole form.
            if (Yii::$app->request->isPost) {
                Yii::$app->session->setFlash('error', 'Nothing was saved: the files sent were larger than the server allows (' . self::uploadLimit() . ' in all). Choose a smaller file.');
            }

            return $this->render('form', ['model' => $model]);
        }
        $stored = [];
        $transaction = null;
        $newFile = null;
        $oldFile = $model->getOldAttribute('download_file');
        try {
            $cover = UploadedFile::getInstance($model, 'cover_image');
            if ($cover !== null) {
                $model->cover_image = $stored[] = (new BlogImageService())->store($cover, Product::UPLOAD_DIR);
            }
            $shots = UploadedFile::getInstancesByName('screenshots');
            $remove = array_map('intval', (array) Yii::$app->request->post('remove_images', []));
            $kept = $model->isNewRecord ? 0 : (int) $model->getImages()->andWhere(['not in', 'id', $remove])->count();
            if ($kept + count($shots) > Product::MAX_IMAGES) {
                throw new \DomainException('A product can have up to ' . Product::MAX_IMAGES . ' screenshots.');
            }
            $paths = [];
            foreach ($shots as $shot) {
                try {
                    $paths[] = $stored[] = (new BlogImageService())->store($shot, Product::UPLOAD_DIR);
                } catch (\DomainException $e) {
                    throw new \DomainException('Screenshot: ' . $e->getMessage());
                }
            }
            $download = UploadedFile::getInstanceByName('download_file');
            if ($download !== null) {
                $newFile = $this->storeDownload($download, $model);
            }
            $oldCover = $model->getOldAttribute('cover_image');
            $transaction = Yii::$app->db->beginTransaction();
            if (!$model->save()) {
                throw new \DomainException('');
            }
            /** @var ProductImage[] $gone */
            $gone = $model->getImages()->andWhere(['id' => $remove])->all();
            foreach ($gone as $image) {
                $image->delete();
            }
            $next = (int) $model->getImages()->max('sort_order') + 1;
            foreach ($paths as $path) {
                (new ProductImage(['product_id' => $model->id, 'path' => $path, 'sort_order' => $next++]))->save(false);
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            // Nothing saved: the new files go, and the form asks for them again rather than losing them silently.
            if ($transaction?->isActive) {
                $transaction->rollBack();
            }
            foreach ($stored as $path) {
                @unlink(Yii::getAlias('@frontend/web/' . $path));
            }
            if ($newFile !== null) {
                @unlink($newFile);
            }
            $model->download_file = $model->getOldAttribute('download_file');
            $model->download_name = $model->getOldAttribute('download_name');
            if (!$e instanceof \DomainException) {
                throw $e;
            }
            $model->cover_image = $model->getOldAttribute('cover_image');
            if ($e->getMessage() !== '') {
                // The rest of the form is checked too, so every problem shows at once.
                $model->validate();
                Yii::$app->session->setFlash('error', $e->getMessage() . ' Nothing was saved: choose the images and download file again.');
            } elseif ($stored !== [] || $newFile !== null) {
                Yii::$app->session->setFlash('warning', 'Fix the errors below, then choose the ' . ($newFile !== null ? ($stored !== [] ? 'images and download file' : 'download file') : 'images') . ' again: they were not kept.');
            }

            return $this->render('form', ['model' => $model]);
        }
        // The replaced file: buyers now get the new one.
        if ($newFile !== null && $oldFile) {
            @unlink(Yii::getAlias('@storage/' . Product::FILES_DIR . '/' . $oldFile));
        }
        // The replaced cover and removed screenshots are no longer shown anywhere.
        foreach (array_filter([$cover !== null ? $oldCover : null, ...array_map(static fn(ProductImage $i): string => $i->path, $gone)]) as $file) {
            @unlink(Yii::getAlias('@frontend/web/' . $file));
        }
        Yii::$app->session->setFlash('success', $model->isPublic()
            ? 'Saved. It is in the store, and in the sitemap by itself.'
            : 'Saved as a draft: it is not in the store until you publish it.');

        return $this->redirect(['update', 'id' => $model->id]);
    }

    /** The largest upload the server takes: the smaller of PHP's two limits, as PHP writes it (e.g. "60M"). */
    public static function uploadLimit(): string
    {
        $limits = [(string) ini_get('upload_max_filesize'), (string) ini_get('post_max_size')];
        usort($limits, static fn(string $a, string $b): int => ini_parse_quantity($a) <=> ini_parse_quantity($b));

        return $limits[0];
    }

    /** The file buyers download: a .zip, kept outside the web root under a name nobody can guess. */
    private function storeDownload(UploadedFile $file, Product $model): string
    {
        if ($file->error !== UPLOAD_ERR_OK) {
            throw new \DomainException('The download file did not upload. It may be larger than the server allows (' . self::uploadLimit() . ').');
        }
        if (strtolower($file->extension) !== 'zip') {
            throw new \DomainException('The download file must be a .zip file.');
        }
        $dir = Yii::getAlias('@storage/' . Product::FILES_DIR);
        FileHelper::createDirectory($dir, 0750);
        $name = bin2hex(random_bytes(16)) . '.zip';
        if (!$file->saveAs($dir . '/' . $name, false)) {
            throw new \RuntimeException('The download file could not be saved.');
        }
        $model->download_file = $name;
        $model->download_name = (trim((string) preg_replace('/[^\w.-]+/', '-', $file->baseName), '-.') ?: 'download') . '.zip';

        return $dir . '/' . $name;
    }

    private function findModel(int $id): Product
    {
        return Product::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
    }
}
