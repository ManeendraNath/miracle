<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\services\BlogImageService;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */
/** @var string $base the public site address, ending in / */

$this->title = 'Blog Media';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Images' => 'Every image uploaded for the blog: covers and pictures in posts, newest first. Uploads are scaled to at most 1600 px wide.',
    'Copy address' => 'Copies the image\'s web address, to paste into a post with the editor\'s image or link button.',
    'Used in' => 'The posts that show the image. An image in use can\'t be deleted; remove it from the post first.',
    'Delete' => 'Only for images no post uses. One that an earlier version of a post used is marked: restoring that version would show a missing picture.',
];
$this->registerJs(<<<'JS'
document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', () => {
    const done = (text) => {
        button.textContent = text;
        setTimeout(() => { button.textContent = 'Copy address'; }, 2500);
    };
    // The clipboard needs https; otherwise show the address to copy by hand.
    (navigator.clipboard ? navigator.clipboard.writeText(button.dataset.copy) : Promise.reject())
        .then(() => done('Copied'), () => window.prompt('Copy the image address:', button.dataset.copy));
}));
JS);
?>
<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('Images uploaded for the blog, and the posts that use them.') ?>
    <div class="card-body">
        <?= Html::beginForm(['upload'], 'post', ['enctype' => 'multipart/form-data', 'class' => 'd-flex flex-wrap gap-2 align-items-center mb-4']) ?>
            <label for="media-image" class="mb-0">Upload an image (JPG, PNG or WebP, up to <?= BlogImageService::MAX_BYTES / 1024 / 1024 ?> MB):</label>
            <input type="file" name="image" id="media-image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm w-auto" required>
            <button type="submit" class="btn btn-sm btn-primary">Upload</button>
        <?= Html::endForm() ?>

        <?php if ($dataProvider->getTotalCount() === 0): ?>
            <p class="text-body-secondary mb-0">No images yet. Upload one here, or add a cover or picture to a post.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($dataProvider->getModels() as $file): ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="border rounded h-100 d-flex flex-column">
                            <a href="<?= Html::encode($base . $file['path']) ?>" target="_blank" rel="noopener" aria-label="Open the image full size (new tab)"><img src="<?= Html::encode($base . $file['path']) ?>" alt="" loading="lazy" class="w-100 rounded-top" style="aspect-ratio: 16 / 10; object-fit: cover; background: #f1f3f6"></a>
                            <div class="p-2 small d-flex flex-column gap-1 flex-grow-1">
                                <div class="text-body-secondary"><?= Yii::$app->formatter->asDate($file['time'], 'php:d M Y') ?> · <?= Yii::$app->formatter->asShortSize($file['size'], 0) ?></div>
                                <?php if ($file['photoOf'] !== null): ?>
                                    <div>Author photo of <?= Html::encode($file['photoOf']) ?></div>
                                <?php elseif ($file['posts']): ?>
                                    <div>Used in:
                                        <?php foreach ($file['posts'] as $i => $post): ?>
                                            <?= $i > 0 ? ', ' : '' ?><?= Html::a(Html::encode($post['title']), ['/blog-post/update', 'id' => $post['id']]) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-body-secondary">Not used in any post<?= $file['inRevision'] ? ' (an earlier version of a post had it)' : '' ?></div>
                                <?php endif; ?>
                                <div class="mt-auto d-flex gap-1 pt-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary" aria-live="polite" data-copy="<?= Html::encode($base . $file['path']) ?>">Copy address</button>
                                    <?php if (!$file['posts'] && $file['photoOf'] === null): ?>
                                        <?= Html::beginForm(['delete'], 'post', ['class' => 'd-inline']) ?>
                                            <?= Html::hiddenInput('path', $file['path']) ?>
                                            <button type="submit" class="btn btn-xs btn-outline-danger" data-confirm="Delete this image for good?">Delete</button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3"><?= LinkPager::widget(['pagination' => $dataProvider->pagination, 'options' => ['class' => 'pagination pagination-sm'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?></div>
        <?php endif; ?>
    </div>
</div>
