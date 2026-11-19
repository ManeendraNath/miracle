<?php

declare(strict_types=1);

use common\models\Product;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Product $model */

$this->title = $model->isNewRecord ? 'Add Product' : 'Edit: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Themes & Plugins', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->isNewRecord ? 'Add' : 'Edit';
$this->params['help'] = [
    'Web address' => 'Made from the name, e.g. /store/aurora-business-theme. Changing it later breaks links to the old address.',
    'Description' => 'Plain text. Leave an empty line between paragraphs.',
    'Features' => 'One a line; shown as a checklist.',
    'Live demo' => 'Where visitors can try it, e.g. https://demo.example.com/aurora. Opens in a new tab.',
    'Prices' => 'Rupees for clients in India, US dollars for clients abroad. Leave both unlimited-sites prices empty to sell only single-site licences.',
    'Images' => 'Cover: shown in the store list and when shared, about 1600 x 1000. Screenshots: up to ' . Product::MAX_IMAGES . ', shown on the product page. JPG, PNG or WebP, up to 5 MB each. The server limits how much one save can carry: if a save fails with many large images, add a few at a time.',
    'Status' => 'Draft: only staff see it. Published: in the store and the sitemap.',
];
$base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
<div class="row">
    <div class="col-xl-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-body">
                <?= $form->field($model, 'name')->textInput(['maxlength' => 150]) ?>
                <?= $form->field($model, 'slug', ['enableClientValidation' => false])->textInput(['maxlength' => 160, 'placeholder' => 'Made from the name']) ?>
                <div class="row">
                    <div class="col-sm-6"><?= $form->field($model, 'technology')->dropDownList(Product::TECHNOLOGIES, ['prompt' => 'Choose…']) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'kind')->dropDownList(Product::KINDS, ['prompt' => 'Choose…'])->hint('A service is a fixed-price job (/services): one price and a delivery time. A care plan (/care-plans) is a monthly price: paying the first month starts a maintenance service that renews monthly. Neither has a file or licence. "Any website" is for these two.') ?></div>
                </div>
                <?php if ($model->isRecurring() && !\common\services\StoreOrderService::plansRenew()): ?>
                    <div class="alert alert-warning">Maintenance automatic billing or the daily billing job is off, so care plans and subscriptions cannot renew: customers are told to contact you instead of buying. Turn both on in Billing Settings and Cron Jobs & Logs.</div>
                <?php endif; ?>
                <?= $form->field($model, 'summary')->textInput(['maxlength' => 300])->hint('One sentence for the store list and search results.') ?>
                <?= $form->field($model, 'description')->textarea(['rows' => 10]) ?>
                <?= $form->field($model, 'features')->textarea(['rows' => 6, 'placeholder' => "Responsive on phones and tablets\nOne-page and multi-page layouts\nContact form ready"]) ?>
                <?= $form->field($model, 'changelog')->textarea(['rows' => 5, 'placeholder' => "1.1.0 (Nov 2026)\n- Dark mode\n- Faster loading\n\n1.0.0 (Oct 2026)\n- First release"])->hint('Newest version first. Shown on the store page and in the update email to buyers.') ?>
                <div class="row">
                    <div class="col-sm-8"><?= $form->field($model, 'demo_url')->textInput(['maxlength' => 255, 'placeholder' => 'https://…']) ?></div>
                    <div class="col-sm-4"><?= $form->field($model, 'version')->textInput(['maxlength' => 20, 'placeholder' => 'e.g. 1.0.0']) ?></div>
                    <div class="col-sm-4"><?= $form->field($model, 'delivery_days')->textInput(['type' => 'number', 'min' => 1, 'max' => 365])->hint('Services only.') ?></div>
                </div>
            </div>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h2 class="card-title h6 mb-0">Prices</h2></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12"><p class="small text-muted">For a service, the single-site price is its fixed price; for a care plan, its monthly price. The unlimited-sites prices are left out for both. For software, the single-site price is the monthly one and the unlimited-sites price the yearly one (both needed).</p></div>
                    <div class="col-sm-6"><?= $form->field($model, 'price_single_inr')->textInput(['inputmode' => 'decimal']) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'price_single_usd')->textInput(['inputmode' => 'decimal']) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'price_unlimited_inr')->textInput(['inputmode' => 'decimal', 'placeholder' => 'Empty = not offered']) ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'price_unlimited_usd')->textInput(['inputmode' => 'decimal', 'placeholder' => 'Empty = not offered']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-body">
                <?= $form->field($model, 'status')->dropDownList([Product::STATUS_DRAFT => 'Draft', Product::STATUS_PUBLISHED => 'Published']) ?>
                <?= $form->field($model, 'sort_order')->textInput(['type' => 'number', 'min' => 0])->hint('Lower numbers come first.') ?>
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-light border ms-2']) ?>
                <?php if (!$model->isNewRecord && $model->isPublic()): ?>
                    <?= Html::a('View in store<span class="visually-hidden"> (opens in a new tab)</span>', $base . '/store/' . $model->slug, ['class' => 'btn btn-link', 'target' => '_blank', 'rel' => 'noopener']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h2 class="card-title h6 mb-0">Download for buyers (themes and plugins)</h2></div>
            <div class="card-body">
                <?php if ($model->download_file): ?>
                    <p class="small mb-2">Current file: <strong><?= Html::encode($model->download_name) ?></strong><?= is_file((string) $model->downloadPath()) ? ', ' . Yii::$app->formatter->asShortSize((int) filesize((string) $model->downloadPath())) : ' (missing on the server: upload it again)' ?></p>
                <?php endif; ?>
                <label class="form-label" for="product-download"><?= $model->download_file ? 'Replace the file' : 'File (.zip)' ?></label>
                <input type="file" id="product-download" name="download_file" class="form-control" accept=".zip,application/zip">
                <?php if (!$model->isNewRecord && $model->hasLicence()): ?>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="notify-buyers" name="notify_buyers" value="1">
                        <label class="form-check-label" for="notify-buyers">Email buyers about this update (<?= (int) \common\services\StoreOrderService::paidBuyers($model)->count() ?> buyer(s))</label>
                        <div class="form-text">Only when a new file is chosen above. Set the version and What's new first: the email includes them.</div>
                    </div>
                <?php endif; ?>
                <div class="form-text">Buyers download the latest file from My Purchases once paid; updates are free. Up to <?= Html::encode(\backend\controllers\ProductController::uploadLimit()) ?>. Not public: only paying buyers can get it.</div>
            </div>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h2 class="card-title h6 mb-0">Images</h2></div>
            <div class="card-body">
                <?php if ($model->cover_image): ?>
                    <?= Html::img($base . '/' . $model->cover_image, ['class' => 'img-fluid rounded mb-2', 'alt' => 'Current cover image']) ?>
                <?php endif; ?>
                <?= $form->field($model, 'cover_image')->fileInput(['accept' => 'image/jpeg,image/png,image/webp'])->label($model->cover_image ? 'Replace cover image' : 'Cover image') ?>
                <div class="mb-3">
                    <label class="form-label" for="product-screenshots">Add screenshots</label>
                    <input type="file" id="product-screenshots" name="screenshots[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                </div>
                <?php if (!$model->isNewRecord && $model->images): ?>
                    <fieldset>
                        <legend class="form-label fs-6">Screenshots (tick to remove)</legend>
                        <div class="row g-2">
                            <?php foreach ($model->images as $image): ?>
                                <div class="col-6">
                                    <label class="d-block border rounded p-1">
                                        <?= Html::img($base . '/' . $image->path, ['class' => 'img-fluid rounded', 'alt' => 'Screenshot ' . $image->id]) ?>
                                        <span class="form-check mt-1"><input type="checkbox" class="form-check-input" name="remove_images[]" value="<?= (int) $image->id ?>"> <span class="form-check-label small">Remove</span></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>
            </div>
        </div>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h2 class="card-title h6 mb-0">Search engines</h2></div>
            <div class="card-body">
                <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 70, 'placeholder' => 'Empty = the name'])->hint('About 60 characters show in results.') ?>
                <?= $form->field($model, 'seo_description')->textarea(['maxlength' => 170, 'rows' => 3, 'placeholder' => 'Empty = the short summary']) ?>
            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
